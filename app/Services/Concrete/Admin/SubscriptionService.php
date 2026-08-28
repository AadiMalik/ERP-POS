<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Status;
use App\Enums\SubscriptionEventType;
use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\Package;
use App\Models\SubscriptionRenewalRequest;
use App\Models\SubscriptionSetting;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Central orchestrator for the subscription lifecycle - the single place
 * that mutates business_subscriptions/businesses lifecycle state, so every
 * other layer (controllers, middleware, dashboard, scheduled command) reads
 * status through computeDisplayStatus() instead of re-deriving it.
 */
class SubscriptionService
{
    protected InvoiceService $invoice_service;
    protected PaymentService $payment_service;
    protected SubscriptionHistoryService $history_service;
    protected FeatureLimitService $feature_limit_service;

    public function __construct(
        InvoiceService $invoice_service,
        PaymentService $payment_service,
        SubscriptionHistoryService $history_service,
        FeatureLimitService $feature_limit_service
    ) {
        $this->invoice_service = $invoice_service;
        $this->payment_service = $payment_service;
        $this->history_service = $history_service;
        $this->feature_limit_service = $feature_limit_service;
    }

    /**
     * Replaces BusinessService's old private subscription() method. When
     * $billing is omitted, defaults reproduce the previous hardcoded
     * behavior exactly (0 discount/tax, cash, "admin created" reference,
     * marked paid immediately).
     */
    public function createInitial(Business $business, Package $package, array $billing = []): BusinessSubscription
    {
        return DB::transaction(function () use ($business, $package, $billing) {
            $billing = array_merge([
                'discount' => 0,
                'discount_type' => 'percentage',
                'tax' => 0,
                'payment_method' => 'cash',
                'payment_reference' => 'admin created',
                'mark_paid' => true,
            ], $billing);

            $billing_cycle = $billing['billing_cycle'] ?? $package->duration_type;
            $start = now();
            $is_trial = (int) $package->trial_days > 0;

            $cycle_price = $package->priceForCycle($billing_cycle);
            if (!$is_trial && $cycle_price === null) {
                throw new Exception('Selected package does not support this billing cycle, or requires a custom quote.');
            }

            $end = $is_trial
                ? $start->copy()->addDays((int) $package->trial_days)
                : $this->computeEndDate($start, $package, $billing_cycle);

            $status = $is_trial
                ? Status::TRIAL
                : ($billing['mark_paid'] ? Status::ACTIVE : Status::PAYMENT_PENDING);

            $subscription = BusinessSubscription::create([
                'business_subscription_id' => generateUuid(),
                'business_id' => $business->business_id,
                'package_id' => $package->package_id,
                'billing_cycle' => $billing_cycle,
                'start_at' => $start,
                'end_at' => $end,
                'subtotal' => $cycle_price ?? 0,
                'discount' => $billing['discount'],
                'discount_type' => $billing['discount_type'],
                'discount_amount' => 0,
                'tax' => $billing['tax'],
                'tax_amount' => 0,
                'total' => $cycle_price ?? 0,
                'payment_status' => $billing['mark_paid'] ? 'paid' : 'unpaid',
                'payment_method' => $billing['payment_method'],
                'payment_reference' => $billing['payment_reference'],
                'status' => $status,
                'is_deleted' => 0,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);

            $invoice = $this->invoice_service->create($subscription, $package, $billing);

            $subscription->update([
                'discount_amount' => $invoice->discount_amount,
                'tax_amount' => $invoice->tax_amount,
                'total' => $invoice->total,
            ]);

            if ($billing['mark_paid']) {
                $this->payment_service->record($invoice, [
                    'amount' => $invoice->total,
                    'payment_method' => $billing['payment_method'],
                    'payment_reference' => $billing['payment_reference'],
                ], Auth::id(), false);
            }

            $business->update([
                'package_id' => $package->package_id,
                'subscription_start' => $subscription->start_at,
                'subscription_end' => $subscription->end_at,
                'current_business_subscription_id' => $subscription->business_subscription_id,
            ]);

            $this->history_service->log(
                $business->business_id,
                $subscription->business_subscription_id,
                SubscriptionEventType::CREATED,
                null,
                $subscription->status,
                null,
                $package->package_id,
                null,
                null,
                Auth::id()
            );

            return $subscription->fresh();
        });
    }

    /**
     * Creates a NEW business_subscriptions row chained to the current one
     * via renewed_from_subscription_id. Used by both the Super-Admin direct
     * renew flow and renewal-request approval. $data: package_id (optional,
     * defaults to current plan), billing_cycle, discount, discount_type,
     * tax, payment (nested: method, reference, proof, amount, notes,
     * confirm bool - default true).
     */
    public function renew(Business $business, array $data): BusinessSubscription
    {
        return DB::transaction(function () use ($business, $data) {
            $current = $this->getCurrentSubscription($business);
            $package = Package::with('modules')->findOrFail($data['package_id'] ?? $current?->package_id ?? $business->package_id);
            $fromPackageId = $current?->package_id ?? $business->package_id;

            if ($package->package_id !== $fromPackageId) {
                $this->feature_limit_service->assertCompatibleWithPackage($business, $package);
            }

            $billing_cycle = $data['billing_cycle'] ?? $package->duration_type;
            $start = ($current && $current->end_at && Carbon::parse($current->end_at)->gt(now()))
                ? Carbon::parse($current->end_at)
                : now();
            $end = $this->computeEndDate($start, $package, $billing_cycle);

            $cycle_price = $package->priceForCycle($billing_cycle);
            if ($cycle_price === null) {
                throw new Exception('Selected package does not support this billing cycle, or requires a custom quote.');
            }

            $payment = $data['payment'] ?? [];
            $mark_paid = $payment['confirm'] ?? true;

            $subscription = BusinessSubscription::create([
                'business_subscription_id' => generateUuid(),
                'business_id' => $business->business_id,
                'package_id' => $package->package_id,
                'billing_cycle' => $billing_cycle,
                'start_at' => $start,
                'end_at' => $end,
                'subtotal' => $cycle_price,
                'discount' => $data['discount'] ?? 0,
                'discount_type' => $data['discount_type'] ?? 'percentage',
                'discount_amount' => 0,
                'tax' => $data['tax'] ?? 0,
                'tax_amount' => 0,
                'total' => $cycle_price,
                'payment_status' => $mark_paid ? 'paid' : 'unpaid',
                'payment_method' => $payment['method'] ?? 'cash',
                'payment_reference' => $payment['reference'] ?? null,
                'status' => $mark_paid ? Status::ACTIVE : Status::PAYMENT_PENDING,
                'renewed_from_subscription_id' => $current->business_subscription_id ?? null,
                'is_deleted' => 0,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);

            $invoice = $this->invoice_service->create($subscription, $package, $data);

            $subscription->update([
                'discount_amount' => $invoice->discount_amount,
                'tax_amount' => $invoice->tax_amount,
                'total' => $invoice->total,
            ]);

            if ($mark_paid) {
                $this->payment_service->record($invoice, [
                    'amount' => $payment['amount'] ?? $invoice->total,
                    'payment_method' => $payment['method'] ?? 'cash',
                    'payment_reference' => $payment['reference'] ?? null,
                    'payment_proof' => $payment['proof'] ?? null,
                    'notes' => $payment['notes'] ?? null,
                ], Auth::id(), false);
            }

            $business_update = [
                'package_id' => $package->package_id,
                'subscription_start' => $subscription->start_at,
                'subscription_end' => $subscription->end_at,
                'current_business_subscription_id' => $subscription->business_subscription_id,
                'grace_period_ends_at' => null,
            ];

            // A renewal resolves an expired state by definition. Suspension
            // is a separate, deliberate admin action and is intentionally
            // left untouched here - only reactivate() clears it.
            if ($business->status === Status::EXPIRED) {
                $business_update['status'] = Status::ACTIVE;
            }

            $business->update($business_update);

            $this->history_service->log(
                $business->business_id,
                $subscription->business_subscription_id,
                SubscriptionEventType::RENEWED,
                $current->status ?? null,
                $subscription->status,
                $current->package_id ?? null,
                $package->package_id,
                null,
                ['renewed_from_subscription_id' => $current->business_subscription_id ?? null],
                Auth::id()
            );

            return $subscription->fresh();
        });
    }

    public function approveRenewalRequest(SubscriptionRenewalRequest $request, array $data = [], ?int $reviewer_id = null): BusinessSubscription
    {
        if ($request->status !== Status::PENDING) {
            throw new Exception('This renewal request has already been reviewed.');
        }

        $reviewer_id = $reviewer_id ?? Auth::id();

        return DB::transaction(function () use ($request, $data, $reviewer_id) {
            $renew_data = array_merge([
                'package_id' => $request->requested_package_id,
                'billing_cycle' => $request->requested_billing_cycle,
            ], $data);

            $subscription = $this->renew($request->business, $renew_data);

            $request->update([
                'status' => Status::APPROVED,
                'reviewer_notes' => $data['reviewer_notes'] ?? null,
                'reviewed_by' => $reviewer_id,
                'reviewed_at' => now(),
                'resulting_subscription_invoice_id' => $subscription->invoices()->latest('date_created')->first()?->subscription_invoice_id,
                'updatedby_id' => $reviewer_id,
                'date_updated' => now(),
            ]);

            $this->history_service->log(
                $request->business_id,
                $subscription->business_subscription_id,
                SubscriptionEventType::RENEWAL_APPROVED,
                Status::PENDING,
                Status::APPROVED,
                null,
                null,
                $data['reviewer_notes'] ?? null,
                null,
                $reviewer_id
            );

            return $subscription;
        });
    }

    public function rejectRenewalRequest(SubscriptionRenewalRequest $request, string $reason, ?int $reviewer_id = null): void
    {
        $reviewer_id = $reviewer_id ?? Auth::id();

        $request->update([
            'status' => Status::REJECTED,
            'reviewer_notes' => $reason,
            'reviewed_by' => $reviewer_id,
            'reviewed_at' => now(),
            'updatedby_id' => $reviewer_id,
            'date_updated' => now(),
        ]);

        $this->history_service->log(
            $request->business_id,
            $request->business_subscription_id,
            SubscriptionEventType::RENEWAL_REJECTED,
            Status::PENDING,
            Status::REJECTED,
            null,
            null,
            $reason,
            null,
            $reviewer_id
        );
    }

    public function requestChanges(SubscriptionRenewalRequest $request, string $notes, ?int $reviewer_id = null): void
    {
        $reviewer_id = $reviewer_id ?? Auth::id();

        $request->update([
            'status' => Status::CHANGES_REQUESTED,
            'reviewer_notes' => $notes,
            'reviewed_by' => $reviewer_id,
            'reviewed_at' => now(),
            'updatedby_id' => $reviewer_id,
            'date_updated' => now(),
        ]);

        $this->history_service->log(
            $request->business_id,
            $request->business_subscription_id,
            SubscriptionEventType::RENEWAL_CHANGES_REQUESTED,
            Status::PENDING,
            Status::CHANGES_REQUESTED,
            null,
            null,
            $notes,
            null,
            $reviewer_id
        );
    }

    /**
     * Never deletes/touches any table other than status/timestamp columns
     * and the append-only history log - business data is untouched.
     */
    public function suspend(Business $business, string $reason, ?int $actor_id = null): void
    {
        $actor_id = $actor_id ?? Auth::id();
        $current = $this->getCurrentSubscription($business);

        $business->update([
            'status' => Status::SUSPENDED,
            'updatedby_id' => $actor_id,
            'date_updated' => now(),
        ]);

        if ($current) {
            $current->update([
                'status' => Status::SUSPENDED,
                'updatedby_id' => $actor_id,
                'date_updated' => now(),
            ]);
        }

        $this->history_service->log(
            $business->business_id,
            $current->business_subscription_id ?? null,
            SubscriptionEventType::SUSPENDED,
            $current->status ?? null,
            Status::SUSPENDED,
            null,
            null,
            $reason,
            null,
            $actor_id
        );
    }

    public function cancel(Business $business, string $reason, ?int $actor_id = null): void
    {
        $actor_id = $actor_id ?? Auth::id();
        $current = $this->getCurrentSubscription($business);

        if ($current) {
            $current->update([
                'status' => Status::CANCELLED,
                'cancelled_at' => now(),
                'updatedby_id' => $actor_id,
                'date_updated' => now(),
            ]);
        }

        $this->history_service->log(
            $business->business_id,
            $current->business_subscription_id ?? null,
            SubscriptionEventType::CANCELLED,
            $current->status ?? null,
            Status::CANCELLED,
            null,
            null,
            $reason,
            null,
            $actor_id
        );
    }

    public function reactivate(Business $business, ?int $actor_id = null): void
    {
        $actor_id = $actor_id ?? Auth::id();
        $current = $this->getCurrentSubscription($business);

        $business->update([
            'status' => Status::ACTIVE,
            'updatedby_id' => $actor_id,
            'date_updated' => now(),
        ]);

        if ($current && $current->status === Status::SUSPENDED) {
            $current->update([
                'status' => Status::ACTIVE,
                'updatedby_id' => $actor_id,
                'date_updated' => now(),
            ]);
        }

        $this->history_service->log(
            $business->business_id,
            $current->business_subscription_id ?? null,
            SubscriptionEventType::REACTIVATED,
            Status::SUSPENDED,
            Status::ACTIVE,
            null,
            null,
            null,
            null,
            $actor_id
        );
    }

    public function getCurrentSubscription(Business $business): ?BusinessSubscription
    {
        if ($business->current_business_subscription_id) {
            $subscription = BusinessSubscription::find($business->current_business_subscription_id);

            if ($subscription) {
                return $subscription;
            }
        }

        return BusinessSubscription::where('business_id', $business->business_id)
            ->where('is_deleted', 0)
            ->orderByDesc('start_at')
            ->first();
    }

    /**
     * The single function that decides between
     * Trial/Active/Expiring Soon/Payment Pending/Grace Period/Suspended/
     * Cancelled/Expired for display - called from the DataTable, dashboard,
     * middleware, and subscription card, so display status is never
     * recomputed differently in two places.
     */
    public function computeDisplayStatus(BusinessSubscription $subscription): string
    {
        if (in_array($subscription->status, [Status::SUSPENDED, Status::CANCELLED])) {
            return $subscription->status;
        }

        $now = now();
        $end = $subscription->end_at ? Carbon::parse($subscription->end_at) : null;

        if ($end && $now->gt($end)) {
            $grace_end = $subscription->grace_period_ends_at ? Carbon::parse($subscription->grace_period_ends_at) : null;

            if ($grace_end && $now->lte($grace_end)) {
                return Status::GRACE_PERIOD;
            }

            return Status::EXPIRED;
        }

        if ($end && in_array($subscription->status, [Status::TRIAL, Status::ACTIVE, Status::PAYMENT_PENDING])) {
            $thresholds = SubscriptionSetting::current()->reminder_thresholds_days ?: [30];
            $max_threshold = max($thresholds);

            if ($now->diffInDays($end, false) <= $max_threshold) {
                return Status::EXPIRING_SOON;
            }
        }

        return $subscription->status;
    }

    /**
     * "Pending Approval" is a property of a renewal-request row's
     * existence, not a stored subscription status - avoids duplicating
     * that state on business_subscriptions.
     */
    public function hasOpenRenewalRequest(Business $business): ?SubscriptionRenewalRequest
    {
        return SubscriptionRenewalRequest::where('business_id', $business->business_id)
            ->where('status', Status::PENDING)
            ->where('is_deleted', 0)
            ->latest('date_created')
            ->first();
    }

    protected function computeEndDate(Carbon $start, Package $package, ?string $billing_cycle): Carbon
    {
        $cycle = $billing_cycle ?? $package->duration_type;

        return match ($cycle) {
            'yearly' => $start->copy()->addYear(),
            'monthly' => $start->copy()->addMonth(),
            default => $start->copy()->addDays((int) $package->duration_days),
        };
    }
}
