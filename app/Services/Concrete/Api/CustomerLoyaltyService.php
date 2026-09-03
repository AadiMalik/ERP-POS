<?php

namespace App\Services\Concrete\Api;

use App\Models\CustomerLoyaltyTransaction;
use App\Services\Concrete\Admin\LoyaltyPointService;

/**
 * Storefront Loyalty Program reads - balance summary + transaction history
 * for the authenticated customer, scoped to a single business. All balance
 * math is owned by the Admin LoyaltyPointService; this class only shapes the
 * storefront-facing payload (mirrors CustomerOrderService's role for orders).
 */
class CustomerLoyaltyService
{
    protected $loyalty_point_service;

    public function __construct(LoyaltyPointService $loyalty_point_service)
    {
        $this->loyalty_point_service = $loyalty_point_service;
    }

    /**
     * Balance summary for the authenticated customer. When the Loyalty
     * Program is off for this business, returns enabled=false with every
     * other field null so the frontend gets a clean "hide the UI" signal
     * instead of an error.
     */
    public function summary(string $business_id, int $customer_id): array
    {
        $enabled = $this->loyalty_point_service->isEnabled($business_id);

        if (!$enabled) {
            return [
                'enabled' => false,
                'available' => null,
                'reserved' => null,
                'redemptionValue' => null,
            ];
        }

        $balances = $this->loyalty_point_service->getBalances($business_id, $customer_id);
        $setting = $this->loyalty_point_service->getSetting($business_id);

        return [
            'enabled' => true,
            'available' => (float) $balances['available'],
            'reserved' => (float) $balances['reserved'],
            'redemptionValue' => (float) ($setting->loyalty_redemption_value ?? 0),
        ];
    }

    public function history(string $business_id, int $customer_id, array $params = []): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $per_page = min(50, max(1, (int) ($params['per_page'] ?? 20)));

        $paginator = CustomerLoyaltyTransaction::where('business_id', $business_id)
            ->where('customer_id', $customer_id)
            ->orderByDesc('date_created')
            ->paginate($per_page, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn ($t) => $this->mapTransaction($t))->values()->all(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    protected function mapTransaction(CustomerLoyaltyTransaction $transaction): array
    {
        return [
            'id' => $transaction->customer_loyalty_transaction_id,
            'transactionType' => $transaction->transaction_type,
            'points' => (float) $transaction->points,
            'monetaryValue' => $transaction->monetary_value !== null ? (float) $transaction->monetary_value : null,
            'availableBalanceAfter' => (float) $transaction->available_balance_after,
            'reservedBalanceAfter' => (float) $transaction->reserved_balance_after,
            'referenceType' => $transaction->reference_type,
            'referenceId' => $transaction->reference_id,
            'description' => $transaction->description,
            'dateCreated' => optional($transaction->date_created)->toIso8601String(),
        ];
    }
}
