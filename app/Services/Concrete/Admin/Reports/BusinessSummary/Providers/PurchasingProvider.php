<?php

namespace App\Services\Concrete\Admin\Reports\BusinessSummary\Providers;

use App\Enums\Status;
use App\Models\Purchase;
use App\Services\Concrete\Admin\Dashboard\DashboardPurchaseService;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryContext;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryInsightFactory as Insight;

class PurchasingProvider
{
    public function __construct(protected DashboardPurchaseService $purchase_service)
    {
    }

    public function collect(BusinessSummaryContext $context): array
    {
        if (!$context->hasModule('inventory') || !$context->canAny([
            'purchase.view',
            'reports.accounts-payable.view',
            'reports.supplier-aging.view',
        ])) {
            return ['kpis' => [], 'insights' => []];
        }

        $scope = [
            'business_id' => $context->business_id,
            'effective_branch_id' => $context->branch_id,
            'start_date' => $context->start_date,
            'end_date' => $context->end_date,
        ];
        $summary = $this->purchase_service->build($scope);
        $pending = $this->pendingPurchases($context);

        $kpis = [
            'purchases' => $summary['total_purchase_amount'] ?? 0,
            'purchase_count' => $summary['total_purchases'] ?? 0,
        ];

        $insights = [];

        if (($summary['total_purchases'] ?? 0) === 0 && $pending === 0) {
            $insights[] = Insight::make([
                'id' => 'purchases_no_activity',
                'module' => 'purchasing',
                'severity' => 'informational',
                'icon' => 'fa-truck-ramp-box',
                'title_key' => 'reports.business_summary.purchases_no_activity_title',
                'description_key' => 'reports.business_summary.no_activity_period',
                'empty' => true,
                'action' => Insight::action($context, 'reports.business_summary.actions.view_purchases', '/admin/purchase', [], 'purchase.view'),
            ]);
        } else {
            $insights[] = Insight::make([
                'id' => 'purchases_overview',
                'module' => 'purchasing',
                'severity' => 'informational',
                'icon' => 'fa-cart-shopping',
                'title_key' => 'reports.business_summary.purchases_title',
                'title_params' => ['amount' => currency($summary['total_purchase_amount'] ?? 0)],
                'description_key' => 'reports.business_summary.purchases_desc',
                'description_params' => ['count' => $summary['total_purchases'] ?? 0],
                'metric' => $summary['total_purchase_amount'] ?? 0,
                'metric_type' => 'money',
                'value' => $summary['total_purchase_amount'] ?? 0,
                'action' => Insight::action($context, 'reports.business_summary.actions.view_purchases', '/admin/purchase', [], 'purchase.view'),
            ]);
        }

        if ($pending > 0) {
            $insights[] = Insight::make([
                'id' => 'pending_purchases',
                'module' => 'purchasing',
                'severity' => 'important',
                'icon' => 'fa-hourglass-start',
                'title_key' => 'reports.business_summary.pending_purchases_title',
                'title_params' => ['count' => $pending],
                'description_key' => 'reports.business_summary.pending_purchases_desc',
                'metric' => $pending,
                'why_key' => 'reports.business_summary.why.pending_purchases',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_purchases', '/admin/purchase', ['status' => Status::PENDING], 'purchase.view'),
            ]);
        }

        return compact('kpis', 'insights');
    }

    protected function pendingPurchases(BusinessSummaryContext $context): int
    {
        return Purchase::query()
            ->where('is_deleted', 0)
            ->where('business_id', $context->business_id)
            ->when($context->branch_id, fn ($q) => $q->where('branch_id', $context->branch_id))
            ->where('status', Status::PENDING)
            ->count();
    }
}
