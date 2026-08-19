<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionRenewalRequest;
use App\Services\Concrete\Admin\FeatureLimitService;
use App\Services\Concrete\Admin\PaymentService;
use App\Services\Concrete\Admin\SubscriptionService;
use App\Support\Subscription\SubscriptionModuleRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

/**
 * Business-Admin-facing self-service. Every method resource-scopes hard to
 * Auth::user()->business_id - never accepts a business id from the request -
 * so a Business Admin structurally cannot reach another business's data.
 */
class MySubscriptionController extends Controller
{
    protected SubscriptionService $subscription_service;
    protected PaymentService $payment_service;
    protected FeatureLimitService $feature_limit_service;

    public function __construct(
        SubscriptionService $subscription_service,
        PaymentService $payment_service,
        FeatureLimitService $feature_limit_service
    ) {
        $this->middleware('permission:my-subscription.manage')->only(['index', 'storeRenewalRequest', 'invoicePdf', 'storePayment']);

        $this->subscription_service = $subscription_service;
        $this->payment_service = $payment_service;
        $this->feature_limit_service = $feature_limit_service;
    }

    public function index()
    {
        $business = Auth::user()->business;

        abort_if(!$business, 403, 'No business associated with this account.');

        $subscription = $this->subscription_service->getCurrentSubscription($business);
        $display_status = $subscription ? $this->subscription_service->computeDisplayStatus($subscription) : null;
        $open_request = $this->subscription_service->hasOpenRenewalRequest($business);

        $invoices = $business->subscriptions()->with('invoices.payments')->orderByDesc('start_at')->get()->pluck('invoices')->flatten();
        $renewal_requests = SubscriptionRenewalRequest::where('business_id', $business->business_id)
            ->orderByDesc('date_created')
            ->get();

        $packages = Package::with('modules')->where('status', 1)->where('is_deleted', 0)->orderBy('order')->get();

        $moduleUsage = $this->buildModuleUsage($business);

        return view('admin.my-subscription.index', compact(
            'business',
            'subscription',
            'display_status',
            'open_request',
            'invoices',
            'renewal_requests',
            'packages',
            'moduleUsage'
        ));
    }

    /**
     * Per-category, per-module usage summary for the "Modules & Usage"
     * section - {label, enabled, used, limit, unlimited, remaining, percent}
     * grouped by SubscriptionModuleRegistry category. Read-only (Phase 1) -
     * does not block anything, just informs the Business Admin.
     */
    protected function buildModuleUsage($business): array
    {
        $business->loadMissing('package.modules');

        $summary = [];

        foreach (SubscriptionModuleRegistry::grouped() as $category => $modules) {
            foreach ($modules as $key => $meta) {
                $enabled = $this->feature_limit_service->hasModule($key, $business);
                $row = [
                    'label' => $meta['label'],
                    'type' => $meta['type'],
                    'enabled' => $enabled,
                ];

                if ($meta['type'] === 'limited') {
                    $usage = $this->feature_limit_service->usage($key, $business);
                    $row = array_merge($row, $usage);
                    $row['percent'] = ($usage['unlimited'] || !$usage['limit'])
                        ? 0
                        : min(100, (int) round(($usage['used'] / $usage['limit']) * 100));
                }

                $summary[$category][$key] = $row;
            }
        }

        return $summary;
    }

    public function storeRenewalRequest(Request $request)
    {
        $business = Auth::user()->business;

        abort_if(!$business, 403, 'No business associated with this account.');

        $request->validate([
            'requested_package_id' => 'required|exists:packages,package_id',
            'requested_billing_cycle' => 'required|in:monthly,yearly',
        ]);

        if ($this->subscription_service->hasOpenRenewalRequest($business)) {
            return redirect()->back()->with('error', 'You already have a pending renewal request awaiting Super Admin review.');
        }

        $current = $this->subscription_service->getCurrentSubscription($business);

        SubscriptionRenewalRequest::create([
            'subscription_renewal_request_id' => generateUuid(),
            'business_id' => $business->business_id,
            'business_subscription_id' => $current->business_subscription_id ?? null,
            'requested_package_id' => $request->requested_package_id,
            'requested_billing_cycle' => $request->requested_billing_cycle,
            'status' => 'pending',
            'requested_notes' => $request->requested_notes,
            'is_deleted' => 0,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);

        return redirect()->route('my-subscription.index')->with('success', 'Your renewal request has been submitted and is awaiting Super Admin approval.');
    }

    public function invoicePdf($subscription_invoice_id)
    {
        $business = Auth::user()->business;

        $invoice = SubscriptionInvoice::with(['business', 'package', 'payments'])
            ->where('business_id', $business->business_id ?? null)
            ->findOrFail($subscription_invoice_id);

        $pdf = Pdf::loadView('admin.subscriptions.pdf.invoice', compact('invoice'));

        return $pdf->stream('invoice_' . $invoice->invoice_no . '.pdf');
    }

    public function storePayment(Request $request, $subscription_invoice_id)
    {
        $business = Auth::user()->business;

        $invoice = SubscriptionInvoice::where('business_id', $business->business_id ?? null)
            ->findOrFail($subscription_invoice_id);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,online',
            'payment_reference' => 'nullable|string',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $proof_path = null;

        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = public_path('uploads/subscription_payments');

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $file->move($path, $fileName);
            $proof_path = $fileName;
        }

        $this->payment_service->record($invoice, [
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'payment_proof' => $proof_path,
            'notes' => $request->notes,
        ], Auth::id(), true);

        return redirect()->route('my-subscription.index')->with('success', 'Payment submitted and is awaiting Super Admin confirmation.');
    }
}
