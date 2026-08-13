<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionRenewalRequest;
use App\Services\Concrete\Admin\SubscriptionService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class SubscriptionRenewalRequestController extends Controller
{
    use ResponseAPI;

    protected SubscriptionService $subscription_service;

    public function __construct(SubscriptionService $subscription_service)
    {
        $this->subscription_service = $subscription_service;
    }

    public function index()
    {
        return view('admin.subscription-renewal-requests.index');
    }

    public function getData(Request $request)
    {
        $wh = [];

        if (!empty($request->status)) {
            $wh[] = ['status', $request->status];
        }

        $datatable = SubscriptionRenewalRequest::with(['business', 'requestedPackage'])
            ->where('is_deleted', 0)
            ->where($wh)
            ->orderByDesc('date_created');

        return DataTables::of($datatable)
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '-';
            })
            ->addColumn('requested_package', function ($item) {
                return $item->requestedPackage->name ?? '-';
            })
            ->addColumn('status', function ($item) {
                $labels = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'changes_requested' => 'info',
                    'cancelled' => 'secondary',
                ];
                $color = $labels[$item->status] ?? 'secondary';
                return "<span class='badge bg-label-{$color}'>" . ucwords(str_replace('_', ' ', $item->status)) . "</span>";
            })
            ->addColumn('action', function ($item) {
                if ($item->status !== 'pending') {
                    return "<a class='btn btn-icon btn-outline-primary' href='" . route('subscriptions.show', $item->business_id) . "'><i class='fa fa-eye'></i></a>";
                }

                return "
                    <button type='button' class='btn btn-sm btn-outline-success mr-1 approve-renewal-request' data-id='{$item->subscription_renewal_request_id}'>Approve</button>
                    <button type='button' class='btn btn-sm btn-outline-danger mr-1 reject-renewal-request' data-id='{$item->subscription_renewal_request_id}'>Reject</button>
                    <button type='button' class='btn btn-sm btn-outline-warning request-changes-renewal-request' data-id='{$item->subscription_renewal_request_id}'>Request Changes</button>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function approve(Request $request, $subscription_renewal_request_id)
    {
        try {
            $renewal_request = SubscriptionRenewalRequest::findOrFail($subscription_renewal_request_id);

            $data = [
                'discount' => $request->discount ?? 0,
                'discount_type' => $request->discount_type ?? 'percentage',
                'tax' => $request->tax ?? 0,
                'reviewer_notes' => $request->reviewer_notes,
                'payment' => [
                    'method' => $request->payment_method ?? 'cash',
                    'reference' => $request->payment_reference,
                    'confirm' => (bool) $request->boolean('mark_paid', true),
                ],
            ];

            $this->subscription_service->approveRenewalRequest($renewal_request, $data);

            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function reject(Request $request, $subscription_renewal_request_id)
    {
        try {
            $renewal_request = SubscriptionRenewalRequest::findOrFail($subscription_renewal_request_id);
            $this->subscription_service->rejectRenewalRequest($renewal_request, $request->reason ?? 'Rejected by Super Admin');

            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function requestChanges(Request $request, $subscription_renewal_request_id)
    {
        try {
            $renewal_request = SubscriptionRenewalRequest::findOrFail($subscription_renewal_request_id);
            $this->subscription_service->requestChanges($renewal_request, $request->notes ?? 'Please review and resubmit.');

            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
