<?php

namespace App\Services\Concrete\Admin\Dashboard;

use App\Services\Concrete\Admin\SubscriptionService;
use Illuminate\Support\Facades\Auth;

/**
 * Orchestrates the Business Dashboard: takes an already-resolved
 * DashboardAccessService scope and folds together every widget-shaped data
 * source into one flat array for the view. Never touches the request
 * directly - HomeController resolves the scope first, this only ever reads
 * from it.
 */
class DashboardService
{
    public function __construct(
        protected DashboardFilterOptionsService $filter_options_service,
        protected DashboardSalesService $sales_service,
        protected DashboardPurchaseService $purchase_service,
        protected DashboardInventoryService $inventory_service,
        protected DashboardPartyService $party_service,
        protected DashboardFinanceService $finance_service,
        protected SubscriptionService $subscription_service
    ) {
    }

    public function build(array $scope): array
    {
        $data = [
            'scope' => $scope,
            'filter_options' => $this->filter_options_service->build($scope),
            'sales' => $this->sales_service->build($scope),
        ];

        if ($scope['tier'] === 1) {
            $data['purchases'] = $this->purchase_service->build($scope);
            $data['inventory'] = $this->inventory_service->build($scope);
            $data['parties'] = $this->party_service->build($scope);

            // Sales-vs-Purchase comparison chart needs both trends aligned to
            // the same date axis (gaps filled with 0), otherwise a combo
            // chart's bars/line would misalign when one series has dates the
            // other lacks.
            if (!empty($data['sales']['daily_trend'])) {
                $data['comparison'] = $this->buildComparisonTrend(
                    $data['sales']['daily_trend'],
                    $data['purchases']['daily_trend'] ?? []
                );
            }

            $business = Auth::user()->business;
            $data['subscription'] = $business ? $this->subscription_service->getCurrentSubscription($business) : null;
            $data['display_status'] = $data['subscription'] ? $this->subscription_service->computeDisplayStatus($data['subscription']) : null;
        }

        if ($scope['is_finance']) {
            $data['finance'] = $this->finance_service->build($scope);
        }

        return $data;
    }

    /**
     * Aligns sales and purchase daily trends onto one shared date axis
     * (union of both keys, sorted), filling missing days with 0 so a combo
     * chart's bars/line stay in sync. Pure array munging - no query of its own.
     */
    protected function buildComparisonTrend(array $salesTrend, array $purchaseTrend): array
    {
        $dates = collect(array_keys($salesTrend))
            ->merge(array_keys($purchaseTrend))
            ->unique()
            ->sort()
            ->values();

        return [
            'categories' => $dates->all(),
            'sales' => $dates->map(fn ($d) => (float) ($salesTrend[$d] ?? 0))->all(),
            'purchases' => $dates->map(fn ($d) => (float) ($purchaseTrend[$d] ?? 0))->all(),
        ];
    }
}
