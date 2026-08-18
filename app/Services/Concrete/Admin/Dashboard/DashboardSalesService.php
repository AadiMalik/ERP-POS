<?php

namespace App\Services\Concrete\Admin\Dashboard;

use App\Services\Concrete\Admin\OrderService;

/**
 * Thin wrapper translating a dashboard scope into OrderService's filter
 * array shape - no query logic of its own, everything is delegated to
 * OrderService so the dashboard and the POS Order History page always agree
 * on what "in scope" means (including Order Taker's forced-today lock).
 */
class DashboardSalesService
{
    public function __construct(protected OrderService $order_service)
    {
    }

    public function build(array $scope): array
    {
        $obj = $this->filterObj($scope);

        $summary = $this->order_service->getDashboardSummary($obj);

        return array_merge($summary, [
            'top_products' => $this->order_service->getTopSellingProducts($obj),
            'recent_orders' => $this->order_service->getRecent($obj),
            'daily_trend' => $this->order_service->getDailyTrend($obj),
        ]);
    }

    protected function filterObj(array $scope): array
    {
        return [
            'business_id' => $scope['business_id'],
            'branch_id' => $scope['effective_branch_id'],
            'start_date' => $scope['start_date'],
            'end_date' => $scope['end_date'],
            'order_type_id' => $scope['order_type_id'],
            'order_source_id' => $scope['order_source_id'],
            'payment_method_id' => $scope['payment_method_id'],
            // Widen visibility to every Tier-1 dashboard role (mirrors
            // DashboardPurchaseService) - without this, applyHistoryFilters()'s
            // default ACL 403s any Tier-1 role outside [SUPERADMIN,
            // BUSINESSADMIN, BRANCHADMIN, POSMANAGER, ORDERTAKER].
            'allow_roles' => DashboardAccessService::TIER1_ROLES,
        ];
    }
}
