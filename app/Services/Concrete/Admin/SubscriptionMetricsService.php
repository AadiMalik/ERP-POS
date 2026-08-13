<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Status;
use App\Models\Business;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionRenewalRequest;
use Carbon\Carbon;

/**
 * Read-only reporting queries for the Super Admin dashboard. Kept separate
 * from SubscriptionService, which is reserved for state-mutating lifecycle
 * operations.
 */
class SubscriptionMetricsService
{
    protected SubscriptionService $subscription_service;

    public function __construct(SubscriptionService $subscription_service)
    {
        $this->subscription_service = $subscription_service;
    }

    public function summary(): array
    {
        $businesses = Business::with('currentSubscription')->where('is_deleted', 0)->get();

        $counts = array_fill_keys([
            Status::TRIAL,
            Status::ACTIVE,
            Status::EXPIRING_SOON,
            Status::PAYMENT_PENDING,
            Status::GRACE_PERIOD,
            Status::SUSPENDED,
            Status::CANCELLED,
            Status::EXPIRED,
            'no_subscription',
        ], 0);

        foreach ($businesses as $business) {
            $subscription = $business->currentSubscription;

            if (!$subscription) {
                $counts['no_subscription']++;
                continue;
            }

            $status = $this->subscription_service->computeDisplayStatus($subscription);
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        $upcoming_renewals = $businesses->filter(function ($business) {
            $subscription = $business->currentSubscription;

            if (!$subscription || !$subscription->end_at) {
                return false;
            }

            $days = now()->diffInDays($subscription->end_at, false);

            return $days >= 0 && $days <= 30 && !in_array($subscription->status, [Status::SUSPENDED, Status::CANCELLED]);
        })->count();

        $current_month_revenue = SubscriptionPayment::where('status', Status::CONFIRMED)
            ->where('is_deleted', 0)
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $pending_payments = SubscriptionPayment::where('status', Status::PENDING)
            ->where('is_deleted', 0)
            ->count();

        $pending_payments_amount = SubscriptionPayment::where('status', Status::PENDING)
            ->where('is_deleted', 0)
            ->sum('amount');

        $pending_approvals = SubscriptionRenewalRequest::where('status', Status::PENDING)
            ->where('is_deleted', 0)
            ->count();

        return [
            'total_businesses' => $businesses->count(),
            'trial' => $counts[Status::TRIAL],
            'active' => $counts[Status::ACTIVE],
            'expiring_soon' => $counts[Status::EXPIRING_SOON],
            'payment_pending' => $counts[Status::PAYMENT_PENDING],
            'grace_period' => $counts[Status::GRACE_PERIOD],
            'suspended' => $counts[Status::SUSPENDED],
            'cancelled' => $counts[Status::CANCELLED],
            'expired' => $counts[Status::EXPIRED],
            'no_subscription' => $counts['no_subscription'],
            'pending_approvals' => $pending_approvals,
            'upcoming_renewals' => $upcoming_renewals,
            'current_month_revenue' => $current_month_revenue,
            'pending_payments_count' => $pending_payments,
            'pending_payments_amount' => $pending_payments_amount,
        ];
    }

    /**
     * Last 6 months of renewal counts and confirmed revenue, oldest first -
     * feeds the dashboard trend chart.
     */
    public function trend(int $months = 6): array
    {
        $labels = [];
        $renewals = [];
        $revenue = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $labels[] = $month->format('M Y');

            $renewals[] = \App\Models\SubscriptionHistory::where('event_type', 'renewed')
                ->whereBetween('date_created', [$start, $end])
                ->count();

            $revenue[] = (float) SubscriptionPayment::where('status', Status::CONFIRMED)
                ->where('is_deleted', 0)
                ->whereBetween('paid_at', [$start, $end])
                ->sum('amount');
        }

        return compact('labels', 'renewals', 'revenue');
    }
}
