<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
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

    public function index()
    {
        return view('admin.subscription-invoices.index');
    }

    public function getData(Request $request)
    {
        $wh = [];

        if (!empty($request->status)) {
            $wh[] = ['status', $request->status];
        }

        $datatable = SubscriptionInvoice::with(['business', 'package'])
            ->where('is_deleted', 0)
            ->where($wh)
            ->orderByDesc('date_created');

        return DataTables::of($datatable)
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '-';
            })
            ->addColumn('package', function ($item) {
                return $item->package->name ?? '-';
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
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-1' title='View' href='" . route('subscription-invoices.show', $item->subscription_invoice_id) . "'><i class='fa fa-eye'></i></a>
                    <a class='btn btn-icon btn-outline-secondary mr-1' target='_blank' title='PDF' href='" . route('subscription-invoices.pdf', $item->subscription_invoice_id) . "'><i class='fa fa-file-pdf'></i></a>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function show($subscription_invoice_id)
    {
        $invoice = SubscriptionInvoice::with(['business', 'package', 'payments', 'subscription'])->findOrFail($subscription_invoice_id);
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
}
