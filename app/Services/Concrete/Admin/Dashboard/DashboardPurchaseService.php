<?php

namespace App\Services\Concrete\Admin\Dashboard;

use App\Services\Concrete\Admin\PurchaseService;

/**
 * Thin wrapper translating a dashboard scope into PurchaseService's
 * dashboard-filter shape. Purchase visibility is widened from
 * PurchaseService::getData()'s own [SUPERADMIN, BUSINESSADMIN] to every
 * Tier-1 dashboard role via the injectable allow_roles - getData() itself
 * (the standalone Purchase List page) is untouched.
 */
class DashboardPurchaseService
{
    public function __construct(protected PurchaseService $purchase_service)
    {
    }

    public function build(array $scope): array
    {
        $obj = [
            'business_id' => $scope['business_id'],
            'branch_id' => $scope['effective_branch_id'],
            'start_date' => $scope['start_date'],
            'end_date' => $scope['end_date'],
            'allow_roles' => DashboardAccessService::TIER1_ROLES,
        ];

        $summary = $this->purchase_service->getDashboardSummary($obj);

        return array_merge($summary, [
            'recent_purchases' => $this->purchase_service->getRecent($obj),
            'daily_trend' => $this->purchase_service->getDailyTrend($obj),
        ]);
    }
}
