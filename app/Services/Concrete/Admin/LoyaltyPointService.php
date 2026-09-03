<?php

namespace App\Services\Concrete\Admin;

use App\Models\CustomerLoyaltyTransaction;
use App\Models\CustomerProfile;
use App\Models\CustomerSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariation;
use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * Owns customer_profiles.loyalty_points (the "available" aggregate),
 * customer_profiles.loyalty_points_reserved (the "reserved" aggregate) and
 * customer_loyalty_transactions (the ledger) - mirrors the aggregate+ledger
 * pattern already used for store credit (CustomerStoreCreditService /
 * customer_store_credit_transactions). Every mutation locks the
 * CustomerProfile row first (lockForUpdate) so concurrent reservations/
 * redemptions for the same customer can't race past each other. Callers must
 * already be inside their own DB transaction (this class never opens/commits
 * one itself, since every caller - OrderService, OrderReturnService - needs
 * this to be atomic with its own posting).
 */
class LoyaltyPointService
{
    protected function lockedProfile(string $businessId, int $customerId): CustomerProfile
    {
        $profile = CustomerProfile::where('business_id', $businessId)
            ->where('user_id', $customerId)
            ->where('is_deleted', 0)
            ->lockForUpdate()
            ->first();

        if (!$profile) {
            throw new Exception('This customer has no profile for this business - loyalty points cannot be applied.');
        }

        return $profile;
    }

    public function getSetting(string $businessId): ?CustomerSetting
    {
        return CustomerSetting::where('business_id', $businessId)->first();
    }

    public function isEnabled(string $businessId): bool
    {
        return (bool) ($this->getSetting($businessId)->loyalty_program ?? false);
    }

    public function getBalances(string $businessId, int $customerId): array
    {
        $profile = CustomerProfile::where('business_id', $businessId)
            ->where('user_id', $customerId)
            ->where('is_deleted', 0)
            ->first();

        return [
            'available' => (float) ($profile->loyalty_points ?? 0),
            'reserved' => (float) ($profile->loyalty_points_reserved ?? 0),
        ];
    }

    /**
     * Whether a specific product/variation contributes to loyalty earning -
     * always true when the business earns on the whole order; only true for
     * products/variations explicitly opted in when it earns per product.
     * Single source of truth for the storefront "coin badge" (website +
     * mobile) and the earning calculation below.
     */
    public function productEligible(string $businessId, Product $product, ?ProductVariation $variation = null): bool
    {
        if (!$this->isEnabled($businessId)) {
            return false;
        }

        $setting = $this->getSetting($businessId);

        if (!$setting || $setting->loyalty_earning_mode !== 'product') {
            return true;
        }

        if ($variation) {
            return (bool) $variation->is_loyalty_enabled;
        }

        return (bool) $product->is_loyalty_enabled;
    }

    /**
     * Points a paid/completed order would earn, given the business's current
     * Loyalty Program configuration. Pure calculation - does not touch any
     * balance. Returns 0 when the program is off, the order has no customer,
     * the order is below the minimum order amount, or (in 'product' mode) no
     * line item is loyalty-eligible.
     */
    public function calculateEarning(Order $order): float
    {
        if (empty($order->user_id) || !$this->isEnabled($order->business_id)) {
            return 0.0;
        }

        $setting = $this->getSetting($order->business_id);

        if (!$setting || (float) $setting->loyalty_every_amount <= 0 || (float) $setting->loyalty_point_rate <= 0) {
            return 0.0;
        }

        if ((float) $order->total < (float) $setting->loyalty_min_order_amount) {
            return 0.0;
        }

        if ($setting->loyalty_earning_mode === 'product') {
            $eligible_base = (float) $order->details()
                ->whereHas('productVariation', fn ($q) => $q->where('is_loyalty_enabled', 1))
                ->sum('total');
        } else {
            $eligible_base = (float) $order->total;
        }

        if ($eligible_base <= 0) {
            return 0.0;
        }

        $units = floor($eligible_base / (float) $setting->loyalty_every_amount);

        return round($units * (float) $setting->loyalty_point_rate, 3);
    }

    /**
     * How many of the customer's available points (and their monetary value)
     * can be redeemed against an order, capped so the discount can never
     * exceed $cap (the order's payable total before loyalty) or the
     * customer's available balance - whichever is smaller.
     */
    public function calculateRedemption(string $businessId, int $customerId, float $cap): array
    {
        if ($cap <= 0 || !$this->isEnabled($businessId)) {
            return ['points' => 0.0, 'value' => 0.0];
        }

        $setting = $this->getSetting($businessId);
        $redemption_value = (float) ($setting->loyalty_redemption_value ?? 0);

        if ($redemption_value <= 0) {
            return ['points' => 0.0, 'value' => 0.0];
        }

        $available = $this->getBalances($businessId, $customerId)['available'];
        $max_value = round($available * $redemption_value, 3);
        $value = min($max_value, round($cap, 3));

        if ($value <= 0) {
            return ['points' => 0.0, 'value' => 0.0];
        }

        return ['points' => round($value / $redemption_value, 3), 'value' => $value];
    }

    protected function pointsValue(string $businessId, float $points): ?float
    {
        $rate = (float) ($this->getSetting($businessId)->loyalty_redemption_value ?? 0);

        return $rate > 0 ? round($points * $rate, 3) : null;
    }

    /**
     * Credits newly-earned points to the available balance for a paid/
     * completed order - safe to call for an ineligible order (no-op, returns
     * 0). Called once per order from OrderService::applyPostedEffects().
     */
    public function earn(Order $order): float
    {
        $points = $this->calculateEarning($order);

        if ($points <= 0) {
            return 0.0;
        }

        $profile = $this->lockedProfile($order->business_id, $order->user_id);
        $available_after = round((float) $profile->loyalty_points + $points, 3);

        $profile->update(['loyalty_points' => $available_after]);

        $this->recordTransaction(
            $order->business_id,
            $order->user_id,
            'earned',
            $points,
            $this->pointsValue($order->business_id, $points),
            $available_after,
            (float) $profile->loyalty_points_reserved,
            'order',
            $order->order_id,
            'Earned from Order #' . $order->daily_order_id
        );

        return $points;
    }

    /**
     * Moves points from available to reserved - unavailable to any other
     * order while reserved. Throws if the customer doesn't have that many
     * available (the authoritative check; any client-side estimate is only a
     * UX convenience).
     */
    public function reserve(string $businessId, int $customerId, float $points, string $referenceType, string $referenceId, ?string $description = null): void
    {
        if ($points <= 0) {
            return;
        }

        $profile = $this->lockedProfile($businessId, $customerId);

        if (round($points, 3) > round((float) $profile->loyalty_points, 3) + 0.0009) {
            throw new Exception('This customer only has ' . number_format((float) $profile->loyalty_points, 2) . ' loyalty points available.');
        }

        $available_after = round((float) $profile->loyalty_points - $points, 3);
        $reserved_after = round((float) $profile->loyalty_points_reserved + $points, 3);

        $profile->update(['loyalty_points' => $available_after, 'loyalty_points_reserved' => $reserved_after]);

        $this->recordTransaction($businessId, $customerId, 'reserved', $points, $this->pointsValue($businessId, $points), $available_after, $reserved_after, $referenceType, $referenceId, $description);
    }

    /**
     * Moves points from reserved back to available - the reservation is no
     * longer needed (order cancelled/voided before consumption, or a draft's
     * cart changed and its reservation is being resynced).
     */
    public function release(string $businessId, int $customerId, float $points, string $referenceType, string $referenceId, ?string $description = null): void
    {
        if ($points <= 0) {
            return;
        }

        $profile = $this->lockedProfile($businessId, $customerId);

        $reserved_after = round(max(0, (float) $profile->loyalty_points_reserved - $points), 3);
        $available_after = round((float) $profile->loyalty_points + $points, 3);

        $profile->update(['loyalty_points' => $available_after, 'loyalty_points_reserved' => $reserved_after]);

        $this->recordTransaction($businessId, $customerId, 'released', $points, $this->pointsValue($businessId, $points), $available_after, $reserved_after, $referenceType, $referenceId, $description);
    }

    /**
     * Permanently spends previously-reserved points - the order that
     * reserved them was paid/completed. Throws if more points are being
     * consumed than are currently reserved.
     */
    public function consume(string $businessId, int $customerId, float $points, string $referenceType, string $referenceId, ?string $description = null): void
    {
        if ($points <= 0) {
            return;
        }

        $profile = $this->lockedProfile($businessId, $customerId);

        if (round($points, 3) > round((float) $profile->loyalty_points_reserved, 3) + 0.0009) {
            throw new Exception('This customer only has ' . number_format((float) $profile->loyalty_points_reserved, 2) . ' loyalty points reserved for this order.');
        }

        $reserved_after = round((float) $profile->loyalty_points_reserved - $points, 3);

        $profile->update(['loyalty_points_reserved' => $reserved_after]);

        $this->recordTransaction($businessId, $customerId, 'consumed', $points, $this->pointsValue($businessId, $points), (float) $profile->loyalty_points, $reserved_after, $referenceType, $referenceId, $description);
    }

    /**
     * Restores previously-consumed points to available - a paid order that
     * had redeemed points was later voided/reversed. Not capped by the
     * current balance (a reversal always restores in full).
     */
    public function reverse(string $businessId, int $customerId, float $points, string $referenceType, string $referenceId, ?string $description = null): void
    {
        if ($points <= 0) {
            return;
        }

        $profile = $this->lockedProfile($businessId, $customerId);
        $available_after = round((float) $profile->loyalty_points + $points, 3);

        $profile->update(['loyalty_points' => $available_after]);

        $this->recordTransaction($businessId, $customerId, 'reversed', $points, $this->pointsValue($businessId, $points), $available_after, (float) $profile->loyalty_points_reserved, $referenceType, $referenceId, $description);
    }

    /**
     * Takes back previously-earned available points - the order/line that
     * earned them was voided or returned. Throws if the customer has already
     * spent enough of their balance that the take-back can't be absorbed
     * (surfaces the mismatch instead of going negative, matching
     * CustomerStoreCreditService::revoke()'s guard).
     */
    public function revokeEarned(string $businessId, int $customerId, float $points, string $referenceType, string $referenceId, ?string $description = null): void
    {
        if ($points <= 0) {
            return;
        }

        $profile = $this->lockedProfile($businessId, $customerId);

        if (round($points, 3) > round((float) $profile->loyalty_points, 3) + 0.0009) {
            throw new Exception('Cannot reverse the loyalty points earned on this order: the customer has already spent some of them (only ' . number_format((float) $profile->loyalty_points, 2) . ' remain available). Resolve manually before reversing.');
        }

        $available_after = round((float) $profile->loyalty_points - $points, 3);

        $profile->update(['loyalty_points' => $available_after]);

        $value = $this->pointsValue($businessId, $points);

        $this->recordTransaction($businessId, $customerId, 'adjusted', -1 * $points, $value !== null ? -1 * $value : null, $available_after, (float) $profile->loyalty_points_reserved, $referenceType, $referenceId, $description);
    }

    /**
     * Manual admin correction to a customer's available balance (+ or -).
     * Guarded against a resulting negative balance.
     */
    public function adjust(string $businessId, int $customerId, float $delta, ?string $description = null): void
    {
        if ((float) $delta === 0.0) {
            return;
        }

        $profile = $this->lockedProfile($businessId, $customerId);
        $available_after = round((float) $profile->loyalty_points + $delta, 3);

        if ($available_after < 0) {
            throw new Exception('This adjustment would make the customer\'s available loyalty points negative.');
        }

        $profile->update(['loyalty_points' => $available_after]);

        $value = $this->pointsValue($businessId, abs($delta));

        $this->recordTransaction($businessId, $customerId, 'adjusted', $delta, $delta < 0 && $value !== null ? -1 * $value : $value, $available_after, (float) $profile->loyalty_points_reserved, 'manual', null, $description);
    }

    /**
     * Idempotent re-reservation for a draft/held order whose loyalty
     * selection may change across repeated save() calls (items added/
     * removed, points toggled): releases whatever is currently net-reserved
     * for this order_id, then reserves the newly-desired amount. Safe to
     * call with 0 (fully releases, reserves nothing).
     */
    public function syncReservation(string $businessId, int $customerId, string $orderId, string $orderReference, float $desiredPoints, string $description): void
    {
        $current = $this->reservedForOrder($orderId);
        $desiredPoints = round(max(0, $desiredPoints), 3);

        if ($current > 0) {
            $this->release($businessId, $customerId, $current, 'order', $orderId, 'Reservation resynced for ' . $orderReference);
        }

        if ($desiredPoints > 0) {
            $this->reserve($businessId, $customerId, $desiredPoints, 'order', $orderId, $description);
        }
    }

    /**
     * Releases whatever is currently net-reserved for an order back to
     * available - used when a draft/held order is cancelled before it was
     * ever posted.
     */
    public function releaseReservedForOrder(string $businessId, ?int $customerId, string $orderId, ?string $description = null): void
    {
        if (empty($customerId)) {
            return;
        }

        $reserved = $this->reservedForOrder($orderId);

        if ($reserved > 0) {
            $this->release($businessId, $customerId, $reserved, 'order', $orderId, $description);
        }
    }

    /**
     * Net points currently reserved (not yet released/consumed) for an
     * order - used by OrderService::cancel()/applyPostedEffects() to know
     * how many points (if any) an order actually has reserved, without the
     * caller needing to separately track it.
     */
    public function reservedForOrder(string $orderId): float
    {
        return $this->netForReference('order', $orderId, 'reserved', ['released', 'consumed']);
    }

    public function consumedForOrder(string $orderId): float
    {
        return $this->netForReference('order', $orderId, 'consumed', ['reversed']);
    }

    public function earnedForOrder(string $orderId): float
    {
        return $this->netForReference('order', $orderId, 'earned', ['adjusted']);
    }

    public function sumPointsForReference(string $referenceType, string $referenceId, string $transactionType): float
    {
        return (float) CustomerLoyaltyTransaction::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('transaction_type', $transactionType)
            ->sum('points');
    }

    protected function netForReference(string $referenceType, string $referenceId, string $positiveType, array $negativeTypes): float
    {
        $positive = (float) CustomerLoyaltyTransaction::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('transaction_type', $positiveType)
            ->sum('points');

        $negative = (float) CustomerLoyaltyTransaction::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->whereIn('transaction_type', $negativeTypes)
            ->sum('points');

        return max(0, round($positive - abs($negative), 3));
    }

    protected function recordTransaction(string $businessId, int $customerId, string $type, float $points, ?float $monetaryValue, float $availableAfter, float $reservedAfter, ?string $referenceType, ?string $referenceId, ?string $description): void
    {
        CustomerLoyaltyTransaction::create([
            'customer_loyalty_transaction_id' => generateUuid(),
            'business_id' => $businessId,
            'customer_id' => $customerId,
            'transaction_type' => $type,
            'points' => $points,
            'monetary_value' => $monetaryValue,
            'available_balance_after' => $availableAfter,
            'reserved_balance_after' => $reservedAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);
    }
}
