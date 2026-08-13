<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\Concrete\Admin\PackageService;
use App\Services\Concrete\Admin\SubscriptionMetricsService;
use App\Services\Concrete\Admin\SubscriptionService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class SubscriptionController extends Controller
{
    use ResponseAPI;

    protected SubscriptionService $subscription_service;
    protected PackageService $package_service;
    protected SubscriptionMetricsService $metrics_service;

    public function __construct(
        SubscriptionService $subscription_service,
        PackageService $package_service,
        SubscriptionMetricsService $metrics_service
    ) {
        $this->subscription_service = $subscription_service;
        $this->package_service = $package_service;
        $this->metrics_service = $metrics_service;
    }

    public function index()
    {
        $packages = $this->package_service->getAll();
        $metrics = $this->metrics_service->summary();
        $trend = $this->metrics_service->trend();

        return view('admin.subscriptions.index', compact('packages', 'metrics', 'trend'));
    }

    public function getData(Request $request)
    {
        $wh = [];

        if (!empty($request->package_id)) {
            $wh[] = ['package_id', $request->package_id];
        }

        $query = Business::with(['package', 'currentSubscription'])
            ->where('is_deleted', 0)
            ->where($wh);

        $subscription_service = $this->subscription_service;

        // display_status is computed (not a stored column), so when
        // filtering by it we resolve the matching businesses in PHP first
        // and hand DataTables a plain collection instead of a query builder.
        if (!empty($request->status_filter)) {
            $source = $query->get()->filter(function ($business) use ($request, $subscription_service) {
                $subscription = $business->currentSubscription;
                return $subscription && $subscription_service->computeDisplayStatus($subscription) === $request->status_filter;
            })->values();
        } else {
            $source = $query;
        }

        return DataTables::of($source)
            ->addColumn('package', function ($item) {
                return $item->package?->name ?? '-';
            })
            ->addColumn('remaining_days', function ($item) {
                if (empty($item->subscription_end)) {
                    return '-';
                }
                return now()->diffInDays($item->subscription_end, false);
            })
            ->addColumn('display_status', function ($item) use ($subscription_service) {
                $subscription = $item->currentSubscription;

                if (!$subscription) {
                    return '<span class="badge bg-label-secondary">No Subscription</span>';
                }

                return $this->statusBadge($subscription_service->computeDisplayStatus($subscription));
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2' title='View'
                     href='" . route('subscriptions.show', $item->business_id) . "'>
                    <i class='fa fa-eye'></i>
                    </a>
                    <a class='btn btn-icon btn-outline-success mr-2' title='Renew'
                     href='" . route('subscriptions.renew.form', $item->business_id) . "'>
                    <i class='fa fa-refresh'></i>
                    </a>
                ";
            })
            ->rawColumns(['package', 'remaining_days', 'display_status', 'action'])
            ->make(true);
    }

    public function show($business_id)
    {
        $business = Business::with(['package'])->findOrFail($business_id);
        $subscription = $this->subscription_service->getCurrentSubscription($business);
        $display_status = $subscription ? $this->subscription_service->computeDisplayStatus($subscription) : null;

        $invoices = $subscription
            ? $business->subscriptions()->with('invoices.payments')->orderByDesc('start_at')->get()->pluck('invoices')->flatten()
            : collect();

        $history = \App\Models\SubscriptionHistory::where('business_id', $business_id)
            ->orderByDesc('date_created')
            ->get();

        $renewal_requests = \App\Models\SubscriptionRenewalRequest::where('business_id', $business_id)
            ->orderByDesc('date_created')
            ->get();

        return view('admin.subscriptions.show', compact('business', 'subscription', 'display_status', 'invoices', 'history', 'renewal_requests'));
    }

    public function renewForm($business_id)
    {
        $business = Business::with('package')->findOrFail($business_id);
        $subscription = $this->subscription_service->getCurrentSubscription($business);
        $packages = $this->package_service->getAll();

        return view('admin.subscriptions.renew', compact('business', 'subscription', 'packages'));
    }

    public function renew(Request $request, $business_id)
    {
        try {
            $business = Business::findOrFail($business_id);

            $data = [
                'package_id' => $request->package_id,
                'billing_cycle' => $request->billing_cycle,
                'discount' => $request->discount ?? 0,
                'discount_type' => $request->discount_type ?? 'percentage',
                'tax' => $request->tax ?? 0,
                'due_date' => $request->due_date,
                'notes' => $request->notes,
                'payment' => [
                    'method' => $request->payment_method ?? 'cash',
                    'reference' => $request->payment_reference,
                    'amount' => $request->payment_amount,
                    'notes' => $request->payment_notes,
                    'confirm' => (bool) $request->boolean('mark_paid', true),
                ],
            ];

            $this->subscription_service->renew($business, $data);

            return redirect()->route('subscriptions.show', $business_id)
                ->with('success', 'Subscription renewed successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function suspend(Request $request, $business_id)
    {
        try {
            $business = Business::findOrFail($business_id);
            $this->subscription_service->suspend($business, $request->reason ?? 'Suspended by Super Admin');

            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function reactivate($business_id)
    {
        try {
            $business = Business::findOrFail($business_id);
            $this->subscription_service->reactivate($business);

            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function cancel(Request $request, $business_id)
    {
        try {
            $business = Business::findOrFail($business_id);
            $this->subscription_service->cancel($business, $request->reason ?? 'Cancelled by Super Admin');

            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function statusBadge(string $status): string
    {
        $labels = [
            Status::TRIAL => ['Trial', 'info'],
            Status::ACTIVE => ['Active', 'success'],
            Status::EXPIRING_SOON => ['Expiring Soon', 'warning'],
            Status::PAYMENT_PENDING => ['Payment Pending', 'warning'],
            Status::GRACE_PERIOD => ['Grace Period', 'warning'],
            Status::SUSPENDED => ['Suspended', 'secondary'],
            Status::CANCELLED => ['Cancelled', 'dark'],
            Status::EXPIRED => ['Expired', 'danger'],
        ];

        [$label, $color] = $labels[$status] ?? [ucfirst($status), 'secondary'];

        return "<span class='badge bg-label-{$color} me-1 mb-1'>{$label}</span>";
    }
}
