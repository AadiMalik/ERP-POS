<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Models\Business;
use App\Models\BusinessIntelligenceSetting;
use App\Models\InventorySetting;
use App\Models\NotificationSetting;
use App\Models\Production;
use App\Models\ServiceSale;
use App\Services\Concrete\Admin\FeatureLimitService;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryContext;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryInsightFactory as Insight;
use App\Services\Concrete\Admin\Reports\BusinessSummary\Providers\FinanceProvider;
use App\Services\Concrete\Admin\Reports\BusinessSummary\Providers\HrmProvider;
use App\Services\Concrete\Admin\Reports\BusinessSummary\Providers\InventoryProvider;
use App\Services\Concrete\Admin\Reports\BusinessSummary\Providers\OperationsProvider;
use App\Services\Concrete\Admin\Reports\BusinessSummary\Providers\PurchasingProvider;
use App\Services\Concrete\Admin\Reports\BusinessSummary\Providers\SalesProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BusinessSummaryReportService
{
    public function __construct(
        protected FeatureLimitService $feature_limit_service,
        protected SalesProvider $sales_provider,
        protected InventoryProvider $inventory_provider,
        protected PurchasingProvider $purchasing_provider,
        protected FinanceProvider $finance_provider,
        protected OperationsProvider $operations_provider,
        protected HrmProvider $hrm_provider
    ) {
    }

    public function build(array $filters): array
    {
        $context = $this->makeContext($filters);
        if (!$context) {
            return $this->emptyResult();
        }

        $sales = $this->sales_provider->collect($context);
        $operations = $this->operations_provider->collect($context);
        $inventory = $this->inventory_provider->collect($context);
        $purchasing = $this->purchasing_provider->collect($context);
        $finance = $this->finance_provider->collect($context);
        $hrm = $this->hrm_provider->collect($context);
        $extra = $this->extraModuleInsights($context);

        $insights = array_merge(
            $sales['insights'] ?? [],
            $operations['insights'] ?? [],
            $inventory['insights'] ?? [],
            $purchasing['insights'] ?? [],
            $finance['insights'] ?? [],
            $hrm['insights'] ?? [],
            $extra
        );

        $buckets = [
            'critical' => [],
            'important' => [],
            'informational' => [],
            'good' => [],
            'excellent' => [],
        ];

        foreach ($insights as $insight) {
            $severity = $insight['severity'] ?? 'informational';
            if (!isset($buckets[$severity])) {
                $severity = 'informational';
            }
            $buckets[$severity][] = $insight;
        }

        foreach ($buckets as $key => $items) {
            usort($buckets[$key], fn ($a, $b) => ($b['impact'] ?? 0) <=> ($a['impact'] ?? 0));
        }

        $kpis = array_merge(
            $sales['kpis'] ?? [],
            $inventory['kpis'] ?? [],
            $purchasing['kpis'] ?? [],
            $finance['kpis'] ?? [],
            $operations['kpis'] ?? [],
            $hrm['kpis'] ?? []
        );

        return [
            'meta' => [
                'title' => $context->is_single_day
                    ? __('reports.business_summary.daily_title')
                    : __('reports.business_summary.period_title'),
                'business_name' => $context->business_name,
                'start_date' => $context->start_date,
                'end_date' => $context->end_date,
                'previous_start_date' => $context->previous_start_date,
                'previous_end_date' => $context->previous_end_date,
                'generated_at' => localDateTime(now()),
                'timezone' => $context->timezone,
                'is_single_day' => $context->is_single_day,
                'modules' => $context->modules,
            ],
            'executive_overview' => $this->executiveOverview($context, $kpis),
            'charts' => array_merge(
                $sales['charts'] ?? [],
                $operations['charts'] ?? [],
                $hrm['charts'] ?? []
            ),
            'critical' => $buckets['critical'],
            'important' => $buckets['important'],
            'informational' => $buckets['informational'],
            'good' => $buckets['good'],
            'excellent' => $buckets['excellent'],
            'kpis' => $kpis,
        ];
    }

    protected function makeContext(array $filters): ?BusinessSummaryContext
    {
        $user = Auth::user();
        $businessId = $filters['business_id'] ?? $user->business_id;
        if (empty($businessId)) {
            $businessId = Business::query()->value('business_id');
        }
        if (empty($businessId)) {
            return null;
        }
        $branchId = !empty($filters['branch_id']) ? $filters['branch_id'] : null;
        $start = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->toDateString() : businessToday();
        $end = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->toDateString() : businessToday();
        if ($end < $start) {
            $end = $start;
        }

        $startCarbon = Carbon::parse($start)->startOfDay();
        $endCarbon = Carbon::parse($end)->endOfDay();
        $days = $startCarbon->diffInDays($endCarbon) + 1;
        $previousEnd = $startCarbon->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1);

        $business = Business::find($businessId);
        $timezone = businessTimezone();

        $filterObj = array_filter([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'start_date' => $start,
            'end_date' => $end,
        ], fn ($v) => $v !== null && $v !== '');

        $previousFilterObj = array_filter([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'start_date' => $previousStart->toDateString(),
            'end_date' => $previousEnd->toDateString(),
        ], fn ($v) => $v !== null && $v !== '');

        $bi = BusinessIntelligenceSetting::firstOrCreate(
            ['business_id' => $businessId],
            $this->biDefaults()
        );
        $inventory = InventorySetting::firstOrCreate(['business_id' => $businessId]);
        $notification = NotificationSetting::firstOrCreate(
            ['business_id' => $businessId],
            ['credit_limit_threshold_percent' => 100, 'payment_due_days_before' => 3]
        );

        $modules = [
            'pos' => $this->feature_limit_service->hasModule('pos', $business),
            'inventory' => $this->feature_limit_service->hasModule('inventory', $business),
            'accounting' => $this->feature_limit_service->hasModule('accounting', $business),
            'hrm' => $this->feature_limit_service->hasModule('hrm', $business),
            'payroll' => $this->feature_limit_service->hasModule('payroll', $business),
            'manufacturing' => $this->feature_limit_service->hasModule('manufacturing', $business),
            'service-management' => $this->feature_limit_service->hasModule('service-management', $business),
        ];

        $permissionNames = [
            'reports.sales-report.view', 'reports.branch-sales.view', 'reports.order-source-sales.view',
            'order.view', 'order-return.view', 'reports.cancelled-orders.view',
            'reports.offline-orders-report.view', 'reports.discount-report.view', 'reports.top-selling.view',
            'reports.voucher-usage.view', 'reports.complimentary-report.view',
            'reports.stock-summary.view', 'reports.stock-valuation.view', 'reports.stock-aging.view',
            'reports.batch-expiry.view', 'reports.stock-loss.view', 'reports.cost-price-adjustment.view',
            'stock.view', 'warehouse.view', 'transfer-note.view', 'purchase.view',
            'reports.accounts-payable.view', 'reports.supplier-aging.view',
            'reports.profit-loss.view', 'reports.expense-report.view', 'reports.customer-aging.view',
            'reports.cash-flow.view', 'reports.due-credit-sales.view',
            'reports.attendance-summary-report.view', 'attendance.view', 'employee.view',
            'leave-request.view', 'reports.late-attendance-report.view',
            'reports.absent-employees-report.view', 'reports.early-checkout-report.view',
            'reports.missing-checkin-checkout-report.view', 'reports.production-report.view',
            'reports.service-sale-report.view',
        ];

        $permissions = [];
        foreach ($permissionNames as $name) {
            $permissions[$name] = $user?->can($name) ?? false;
        }

        return new BusinessSummaryContext(
            business_id: $businessId,
            branch_id: $branchId,
            start_date: $start,
            end_date: $end,
            previous_start_date: $previousStart->toDateString(),
            previous_end_date: $previousEnd->toDateString(),
            timezone: $timezone,
            business_name: $business->name ?? '',
            is_single_day: $start === $end,
            modules: $modules,
            thresholds: [
                'discount_change_threshold_percent' => (float) $bi->discount_change_threshold_percent,
                'voucher_change_threshold_percent' => (float) $bi->voucher_change_threshold_percent,
                'complimentary_sales_percent_threshold' => (float) $bi->complimentary_sales_percent_threshold,
                'high_discount_percent_threshold' => (float) $bi->high_discount_percent_threshold,
                'high_return_rate_percent' => (float) $bi->high_return_rate_percent,
                'high_cancellation_rate_percent' => (float) $bi->high_cancellation_rate_percent,
                'dead_stock_days' => (int) $bi->dead_stock_days,
                'slow_moving_days' => (int) $bi->slow_moving_days,
                'delayed_order_hours' => (int) $bi->delayed_order_hours,
                'offline_sync_stale_hours' => (int) $bi->offline_sync_stale_hours,
                'high_waste_percent_of_stock' => (float) $bi->high_waste_percent_of_stock,
                'attendance_repeat_late_count' => (int) $bi->attendance_repeat_late_count,
                'low_margin_percent' => (float) $bi->low_margin_percent,
                'excellent_sales_growth_percent' => (float) $bi->excellent_sales_growth_percent,
                'near_expiry_days' => (int) ($inventory->near_expiry_days ?? 30),
                'low_stock_quantity' => (int) ($inventory->low_stock_quantity ?? 5),
                'credit_limit_threshold_percent' => (int) ($notification->credit_limit_threshold_percent ?? 100),
                'payment_due_days_before' => (int) ($notification->payment_due_days_before ?? 3),
            ],
            permissions: $permissions,
            filter_obj: $filterObj,
            previous_filter_obj: $previousFilterObj
        );
    }

    protected function executiveOverview(BusinessSummaryContext $context, array $kpis): array
    {
        $cards = [];

        $push = function (string $id, string $labelKey, $value, string $type, string $icon, ?string $delta = null) use (&$cards) {
            if ($value === null) {
                return;
            }
            $cards[] = [
                'id' => $id,
                'label' => __($labelKey),
                'value' => $type === 'money' ? currency($value) : (string) $value,
                'raw' => $value,
                'icon' => $icon,
                'delta' => $delta,
            ];
        };

        $push('sales', 'reports.business_summary.kpi_sales', $kpis['sales'] ?? null, 'money', 'fa-chart-line', $this->deltaText($kpis['sales_delta'] ?? null));
        $push('orders', 'reports.business_summary.kpi_orders', $kpis['orders'] ?? null, 'count', 'fa-receipt', $this->deltaText($kpis['order_delta'] ?? null));
        $push('gross_profit', 'reports.business_summary.kpi_gross_profit', $kpis['gross_profit'] ?? null, 'money', 'fa-scale-balanced');
        $push('expenses', 'reports.business_summary.kpi_expenses', $kpis['expenses'] ?? null, 'money', 'fa-money-bill-wave');
        $push('receivables', 'reports.business_summary.kpi_receivables', $kpis['receivables'] ?? null, 'money', 'fa-hand-holding-dollar');
        $push('purchases', 'reports.business_summary.kpi_purchases', $kpis['purchases'] ?? null, 'money', 'fa-cart-shopping');
        $push('stock_value', 'reports.business_summary.kpi_stock_value', $kpis['stock_value'] ?? null, 'money', 'fa-warehouse');
        $push('cash_bank', 'reports.business_summary.kpi_cash_bank', $kpis['cash_bank'] ?? null, 'money', 'fa-building-columns');

        return $cards;
    }

    protected function extraModuleInsights(BusinessSummaryContext $context): array
    {
        $insights = [];

        if ($context->hasModule('manufacturing') && $context->can('reports.production-report.view')) {
            $count = Production::query()
                ->where('is_deleted', 0)
                ->where('business_id', $context->business_id)
                ->when($context->branch_id, fn ($q) => $q->where('branch_id', $context->branch_id))
                ->whereBetween('manufacturing_date', [
                    businessStartOfDay($context->start_date),
                    businessEndOfDay($context->end_date),
                ])
                ->count();

            $insights[] = Insight::make([
                'id' => 'production',
                'module' => 'manufacturing',
                'severity' => $count > 0 ? 'informational' : 'informational',
                'icon' => 'fa-industry',
                'title_key' => $count > 0 ? 'reports.business_summary.production_title' : 'reports.business_summary.production_none_title',
                'title_params' => ['count' => $count],
                'description_key' => $count > 0 ? 'reports.business_summary.production_desc' : 'reports.business_summary.no_activity_period',
                'metric' => $count,
                'empty' => $count === 0,
                'action' => Insight::action($context, 'reports.business_summary.actions.view_production', '/admin/reports/production', [], 'reports.production-report.view'),
            ]);
        }

        if ($context->hasModule('service-management') && $context->can('reports.service-sale-report.view')) {
            $row = ServiceSale::query()
                ->where('is_deleted', 0)
                ->where('business_id', $context->business_id)
                ->when($context->branch_id, fn ($q) => $q->where('branch_id', $context->branch_id))
                ->whereBetween('service_sale_date', [
                    businessStartOfDay($context->start_date),
                    businessEndOfDay($context->end_date),
                ])
                ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total), 0) as total')
                ->first();

            $insights[] = Insight::make([
                'id' => 'service_sales',
                'module' => 'service-management',
                'severity' => 'informational',
                'icon' => 'fa-concierge-bell',
                'title_key' => 'reports.business_summary.service_sales_title',
                'title_params' => ['amount' => currency($row->total ?? 0)],
                'description_key' => ($row->cnt ?? 0) > 0
                    ? 'reports.business_summary.service_sales_desc'
                    : 'reports.business_summary.no_activity_period',
                'description_params' => ['count' => $row->cnt ?? 0],
                'metric' => (float) ($row->total ?? 0),
                'metric_type' => 'money',
                'value' => (float) ($row->total ?? 0),
                'empty' => (int) ($row->cnt ?? 0) === 0,
                'action' => Insight::action($context, 'reports.business_summary.actions.view_service_sales', '/admin/reports/service-sale-report', [], 'reports.service-sale-report.view'),
            ]);
        }

        return $insights;
    }

    protected function deltaText(?float $delta): ?string
    {
        if ($delta === null) {
            return null;
        }

        $sign = $delta > 0 ? '+' : '';

        return $sign . Insight::formatMetric($delta, 'percent');
    }

    protected function emptyResult(): array
    {
        $today = businessToday();

        return [
            'meta' => [
                'title' => __('reports.business_summary.period_title'),
                'business_name' => '',
                'start_date' => $today,
                'end_date' => $today,
                'previous_start_date' => $today,
                'previous_end_date' => $today,
                'generated_at' => localDateTime(now()),
                'timezone' => businessTimezone(),
                'is_single_day' => true,
                'modules' => [],
            ],
            'executive_overview' => [],
            'charts' => [],
            'critical' => [],
            'important' => [],
            'informational' => [],
            'good' => [],
            'excellent' => [],
            'kpis' => [],
        ];
    }

    protected function biDefaults(): array
    {
        return [
            'discount_change_threshold_percent' => 5,
            'voucher_change_threshold_percent' => 5,
            'complimentary_sales_percent_threshold' => 5,
            'high_discount_percent_threshold' => 15,
            'high_return_rate_percent' => 10,
            'high_cancellation_rate_percent' => 10,
            'dead_stock_days' => 90,
            'slow_moving_days' => 30,
            'delayed_order_hours' => 24,
            'offline_sync_stale_hours' => 24,
            'high_waste_percent_of_stock' => 2,
            'attendance_repeat_late_count' => 3,
            'low_margin_percent' => 10,
            'excellent_sales_growth_percent' => 20,
        ];
    }
}
