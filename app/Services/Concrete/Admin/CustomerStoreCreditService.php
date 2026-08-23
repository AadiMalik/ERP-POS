<?php

namespace App\Services\Concrete\Admin;

use App\Models\CustomerProfile;
use App\Models\CustomerStoreCreditTransaction;
use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * Owns customer_profiles.store_credit_balance (the aggregate) and
 * customer_store_credit_transactions (the ledger) - mirrors the existing
 * aggregate+ledger pattern used for stock (ProductVariationStock +
 * ProductVariationStockTransaction / ProductVariationStockService).
 * Every mutation locks the CustomerProfile row first (lockForUpdate) so two
 * concurrent redemptions/issuances for the same customer can't race past
 * each other - same concurrency discipline as the Phase 1 stock hardening.
 * Callers must already be inside their own DB transaction (this class never
 * opens/commits one itself, since every caller - OrderReturnService,
 * OrderService - needs this to be atomic with its own posting).
 */
class CustomerStoreCreditService
{
    protected function lockedProfile(string $businessId, int $customerId): CustomerProfile
    {
        $profile = CustomerProfile::where('business_id', $businessId)
            ->where('user_id', $customerId)
            ->where('is_deleted', 0)
            ->lockForUpdate()
            ->first();

        if (!$profile) {
            throw new Exception('This customer has no profile for this business - store credit cannot be applied.');
        }

        return $profile;
    }

    public function getBalance(string $businessId, int $customerId): float
    {
        return (float) (CustomerProfile::where('business_id', $businessId)
            ->where('user_id', $customerId)
            ->where('is_deleted', 0)
            ->value('store_credit_balance') ?? 0);
    }

    /**
     * Increase a customer's store credit balance - e.g. an approved return
     * with no refund method chosen.
     */
    public function issue(string $businessId, int $customerId, float $amount, string $referenceType, string $referenceId, ?string $description = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $profile = $this->lockedProfile($businessId, $customerId);
        $balanceAfter = round((float) $profile->store_credit_balance + $amount, 3);

        $profile->update(['store_credit_balance' => $balanceAfter]);

        $this->recordTransaction($businessId, $customerId, 'issued', $amount, $balanceAfter, $referenceType, $referenceId, $description);
    }

    /**
     * Decrease a customer's store credit balance - spent as a POS payment.
     * Throws if the redemption would exceed the current balance (the
     * authoritative check - any client-side "amount <= balance" check is
     * only a UX convenience).
     */
    public function redeem(string $businessId, int $customerId, float $amount, string $referenceType, string $referenceId, ?string $description = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $profile = $this->lockedProfile($businessId, $customerId);

        if (round($amount, 3) > round((float) $profile->store_credit_balance, 3) + 0.0009) {
            throw new Exception('This customer only has ' . number_format((float) $profile->store_credit_balance, 2) . ' in store credit available.');
        }

        $balanceAfter = round((float) $profile->store_credit_balance - $amount, 3);

        $profile->update(['store_credit_balance' => $balanceAfter]);

        $this->recordTransaction($businessId, $customerId, 'redeemed', $amount, $balanceAfter, $referenceType, $referenceId, $description);
    }

    /**
     * Restore a previously-redeemed amount - e.g. the order that redeemed it
     * was voided. Not capped by the current balance (a reversal always
     * restores in full, regardless of what's happened to the balance since).
     */
    public function reverse(string $businessId, int $customerId, float $amount, string $referenceType, string $referenceId, ?string $description = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $profile = $this->lockedProfile($businessId, $customerId);
        $balanceAfter = round((float) $profile->store_credit_balance + $amount, 3);

        $profile->update(['store_credit_balance' => $balanceAfter]);

        $this->recordTransaction($businessId, $customerId, 'reversed', $amount, $balanceAfter, $referenceType, $referenceId, $description);
    }

    /**
     * Take back a previously-issued amount - the return that issued it was
     * itself un-approved/reversed. Throws if the customer has already
     * spent enough of it that the balance can't absorb the take-back (the
     * GL-side journal entry is still reversed either way - this surfaces
     * the mismatch instead of silently going negative, matching this
     * codebase's other "block rather than corrupt a balance" guards).
     */
    public function revoke(string $businessId, int $customerId, float $amount, string $referenceType, string $referenceId, ?string $description = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $profile = $this->lockedProfile($businessId, $customerId);

        if (round($amount, 3) > round((float) $profile->store_credit_balance, 3) + 0.0009) {
            throw new Exception('Cannot reverse this return: the customer has already spent some of the store credit it issued (only ' . number_format((float) $profile->store_credit_balance, 2) . ' remains). Resolve manually before reversing.');
        }

        $balanceAfter = round((float) $profile->store_credit_balance - $amount, 3);

        $profile->update(['store_credit_balance' => $balanceAfter]);

        $this->recordTransaction($businessId, $customerId, 'revoked', $amount, $balanceAfter, $referenceType, $referenceId, $description);
    }

    /**
     * Sum of 'redeemed' minus 'reversed' transactions tied to a specific
     * order - used by OrderService::void() to know how much store credit
     * (if any) that order actually redeemed, without the caller needing to
     * separately track it.
     */
    public function redeemedForOrder(string $orderId): float
    {
        $redeemed = (float) CustomerStoreCreditTransaction::where('reference_type', 'order')
            ->where('reference_id', $orderId)
            ->where('transaction_type', 'redeemed')
            ->sum('amount');

        $reversed = (float) CustomerStoreCreditTransaction::where('reference_type', 'order')
            ->where('reference_id', $orderId)
            ->where('transaction_type', 'reversed')
            ->sum('amount');

        return max(0, round($redeemed - $reversed, 3));
    }

    protected function recordTransaction(string $businessId, int $customerId, string $type, float $amount, float $balanceAfter, string $referenceType, string $referenceId, ?string $description): void
    {
        CustomerStoreCreditTransaction::create([
            'customer_store_credit_transaction_id' => generateUuid(),
            'business_id' => $businessId,
            'customer_id' => $customerId,
            'transaction_type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);
    }
}
