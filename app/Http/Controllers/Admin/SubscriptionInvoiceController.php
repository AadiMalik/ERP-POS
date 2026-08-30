<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Services\Concrete\Admin\InvoiceService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class SubscriptionInvoiceController extends Controller
{
    use ResponseAPI;

    protected InvoiceService $invoice_service;

    public function __construct(InvoiceService $invoice_service)
    {
        $this->middleware('permission:subscription.manage');

        $this->invoice_service = $invoice_service;
    }

    public function index(Request $request)
    {
        $pendingCount = $this->invoice_service->pendingPaymentCount();
        $defaultRequestType = $request->get('request_type');
        // Default: pending payments only (unless explicitly turned off with payment_pending=0)
        $defaultPaymentPending = !$request->has('payment_pending') || $request->boolean('payment_pending');

        return view('admin.subscription-invoices.index', compact(
            'pendingCount',
            'defaultRequestType',
            'defaultPaymentPending'
        ));
    }

    public function getData(Request $request)
    {
        $datatable = SubscriptionInvoice::with(['business', 'package', 'payments'])
            ->where('is_deleted', 0);

        if (!empty($request->status)) {
            $datatable->where('status', $request->status);
        }

        if (!empty($request->request_type) && in_array($request->request_type, ['new', 'renew'], true)) {
            $datatable->where('request_type', $request->request_type);
        }

        if ($request->boolean('payment_pending')) {
            $datatable->whereHas('payments', function ($q) {
                $q->where('status', Status::PENDING)->where('is_deleted', 0);
            });
        }

        $datatable->orderByRaw("CASE WHEN EXISTS (
            SELECT 1 FROM subscription_payments sp
            WHERE sp.subscription_invoice_id = subscription_invoices.subscription_invoice_id
              AND sp.status = 'pending'
              AND sp.is_deleted = 0
        ) THEN 0 ELSE 1 END")
            ->orderByDesc('date_created');

        return DataTables::of($datatable)
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '-';
            })
            ->addColumn('package', function ($item) {
                return $item->package->name ?? '-';
            })
            ->addColumn('request_type', function ($item) {
                $type = $item->request_type ?: 'new';
                $color = $type === 'renew' ? 'info' : 'primary';
                $label = $type === 'renew' ? 'Renew' : 'New';

                return "<span class='badge bg-label-{$color}'>{$label}</span>";
            })
            ->addColumn('total', function ($item) {
                return currency($item->total ?? 0);
            })
            ->addColumn('status', function ($item) {
                $labels = [
                    'draft' => 'secondary',
                    'unpaid' => 'warning',
                    'partially_paid' => 'info',
                    'paid' => 'success',
                    'void' => 'dark',
                ];
                $color = $labels[$item->status] ?? 'secondary';
                return "<span class='badge bg-label-{$color}'>" . ucwords(str_replace('_', ' ', $item->status)) . "</span>";
            })
            ->addColumn('payment_status', function ($item) {
                $payment = $item->payments->where('is_deleted', 0)->sortByDesc('date_created')->first();
                if (!$payment) {
                    return '<span class="badge bg-label-secondary">None</span>';
                }
                $map = [
                    'pending' => 'warning',
                    'confirmed' => 'success',
                    'rejected' => 'danger',
                ];
                $color = $map[$payment->status] ?? 'secondary';
                $method = ucwords(str_replace('_', ' ', $payment->payment_method ?? ''));
                $refLabel = ($payment->payment_method === 'bank_transfer') ? 'Bank Ref' : 'Ref';
                $ref = $payment->payment_reference
                    ? e($refLabel . ': ' . $payment->payment_reference)
                    : '<span class="text-muted">No reference</span>';
                $receipt = '';
                if ($payment->payment_proof) {
                    $url = asset('public/uploads/subscription_payments/' . $payment->payment_proof);
                    $receipt = "<div class='small mt-1'><a href='{$url}' target='_blank'><i class='fa fa-file'></i> Receipt</a></div>";
                } else {
                    $receipt = "<div class='small text-muted mt-1'>No receipt</div>";
                }

                return "<span class='badge bg-label-{$color}'>" . ucfirst($payment->status) . "</span>"
                    . ($method ? "<div class='small text-muted mt-1'>" . e($method) . "</div>" : '')
                    . "<div class='small mt-1'>{$ref}</div>"
                    . $receipt;
            })
            ->addColumn('payment_method', function ($item) {
                $payment = $item->payments->where('is_deleted', 0)->sortByDesc('date_created')->first();
                if (!$payment) {
                    return '-';
                }

                return ucwords(str_replace('_', ' ', $payment->payment_method ?? '-'));
            })
            ->addColumn('date_created', function ($item) {
                return $item->date_created ? localDateTime($item->date_created) : '-';
            })
            ->addColumn('action', function ($item) {
                $payment = $item->payments->where('is_deleted', 0)->where('status', Status::PENDING)->sortByDesc('date_created')->first();
                $actions = "
                    <a class='btn btn-icon btn-outline-primary mr-1' title='View / Verify' href='" . route('subscription-invoices.show', $item->subscription_invoice_id) . "'><i class='fa fa-eye'></i></a>
                    <a class='btn btn-icon btn-outline-secondary mr-1' target='_blank' title='PDF' href='" . route('subscription-invoices.pdf', $item->subscription_invoice_id) . "'><i class='fa fa-file-pdf'></i></a>
                ";
                if ($payment) {
                    $actions .= "
                        <button type='button' class='btn btn-icon btn-outline-success mr-1 approve-payment-row' title='Confirm Payment' data-id='{$payment->subscription_payment_id}'><i class='fa fa-check'></i></button>
                        <button type='button' class='btn btn-icon btn-outline-warning mr-1 reject-payment-row' title='Reject Payment' data-id='{$payment->subscription_payment_id}'><i class='fa fa-times'></i></button>
                    ";
                }
                $actions .= "<button type='button' class='btn btn-icon btn-outline-danger delete-invoice' data-id='{$item->subscription_invoice_id}' title='Delete'><i class='fa fa-trash'></i></button>";

                return $actions;
            })
            ->rawColumns(['request_type', 'status', 'payment_status', 'action'])
            ->make(true);
    }

    public function show($subscription_invoice_id)
    {
        $invoice = SubscriptionInvoice::with(['business', 'package', 'payments', 'subscription'])
            ->findOrFail($subscription_invoice_id);

        return view('admin.subscription-invoices.show', compact('invoice'));
    }

    public function pdf($subscription_invoice_id)
    {
        $invoice = SubscriptionInvoice::with(['business', 'package', 'payments'])->findOrFail($subscription_invoice_id);
        $pdf = Pdf::loadView('admin.subscriptions.pdf.invoice', compact('invoice'));

        return $pdf->stream('invoice_' . $invoice->invoice_no . '.pdf');
    }

    public function void(Request $request, $subscription_invoice_id)
    {
        try {
            $invoice = SubscriptionInvoice::findOrFail($subscription_invoice_id);
            $this->invoice_service->void($invoice, $request->reason ?? 'Voided by Super Admin', Auth::id());

            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($subscription_invoice_id)
    {
        try {
            $invoice = SubscriptionInvoice::where('is_deleted', 0)->findOrFail($subscription_invoice_id);
            $this->invoice_service->softDelete($invoice, Auth::id());

            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function pendingCount()
    {
        return $this->success(Message::FETCH, [
            'count' => $this->invoice_service->pendingPaymentCount(),
        ]);
    }
}
