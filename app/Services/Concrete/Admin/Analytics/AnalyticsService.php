<?php

namespace App\Services\Concrete\Admin\Analytics;

use App\Models\OrderReturn;
use App\Services\Concrete\Admin\Analytics\Support\PeriodComparisonService;
use App\Services\Concrete\Admin\Dashboard\DashboardAccessService;
use App\Services\Concrete\Admin\Dashboard\DashboardFinanceService;
use App\Services\Concrete\Admin\Dashboard\DashboardInventoryService;
use App\Services\Concrete\Admin\OrderService;
use App\Services\Concrete\Admin\PurchaseService;
use App\Services\Concrete\Admin\Reports\Orders\BranchSalesReportService;
use App\Services\Concrete\Admin\Reports\Orders\CustomerSalesReportService;
use App\Services\Concrete\Admin\Reports\Orders\LoyaltyReportService;
use App\Services\Concrete\Admin\Reports\Orders\OrderSourceSalesReportService;
use App\Services\Concrete\Admin\Reports\Orders\PaymentMethodSalesReportService;
use App\Services\Concrete\Admin\Reports\Orders\TopSellingReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Orchestrates the Analytics dashboard: mirrors DashboardService::build()'s
 * composition style - every widget except product-margin/customer-segments/
 * slow-moving is a thin call into an already-existing dashboard/report
 * service, so Analytics numbers for a given business/branch/date range are
 * never computed twice with two different formulas.
 *
 * This class owns the ONLY Cache::remember() usage in the reports/dashboard
 * layer (grep confirmed zero prior usage anywhere in
 * app/Services/Concrete/Admin/{Dashboard,Reports}) - a deliberate new
 * convention, documented in resources/docs/developer/22-analytics-bi.md, not
 * a silent deviation. Caching lives only here, never inside the reused
 * report services themselves, so those stay pure/uncached and safe to call
 * from anywhere else in the app. TTL-only invalidation (5 min) - no
 * event-driven busting, matching the rest of the codebase's "compute live
 * every request" norm, just capped to a short staleness window.
 */
class AnalyticsService
{
    protected const CACHE_TTL = 300;

    protected const WIDGETS = [
        'sales-overview', 'purchases-overview', 'inventory-summary', 'finance-summary',
        'top-products', 'top-customers', 'branch-comparison', 'order-source-breakdown',
        'payment-method-breakdown', 'loyalty-summary',
        'product-margin', 'customer-segments', 'slow-moving',
    ];

    public function __construct(
        protected OrderService $order_service,
        protected PurchaseService $purchase_service,
        protected DashboardInventoryService $inventory_service,
        protected DashboardFinanceService $finance_service,
        protected TopSellingReportService $top_selling_service,
        protected CustomerSalesReportService $customer_sales_service,
        protected BranchSalesReportService $branch_sales_service,
        protected OrderSourceSalesReportService $order_source_sales_service,
        protected PaymentMethodSalesReportService $payment_method_sales_service,
        protected LoyaltyReportService $loyalty_report_service,
        protected ProductMarginReportService $product_margin_service,
        protected CustomerSegmentService $customer_segment_service,
        protected SlowMovingProductService $slow_moving_service,
        protected PeriodComparisonService $period_comparison
    ) {
    }

    public static function widgetKeys(): array
    {
        return self::WIDGETS;
    }

    public function widgetData(string $widget, array $scope): array
    {
        abort_unless(in_array($widget, self::WIDGETS, true), 404);

        $method = Str::camel($widget);

        return Cache::remember(
            $this->cacheKey($widget, $scope),
            self::CACHE_TTL,
            fn () => $this->{$method}($scope)
        );
    }

    protected function cacheKey(string $widget, array $scope): string
    {
        $filter_fingerprint = md5(json_encode([
            $scope['start_date']->format('Y-m-d H:i'),
            $scope['end_date']->format('Y-m-d H:i'),
            $scope['product_id'] ?? null,
            $scope['category_id'] ?? null,
            $scope['brand_id'] ?? null,
            $scope['customer_id'] ?? null,
            $scope['order_type_id'] ?? null,
            $scope['order_source_id'] ?? null,
            $scope['payment_method_id'] ?? null,
            !empty($scope['compare_previous_period']),
        ]));

        return sprintf(
            'analytics:%s:%s:%s:%s',
            $scope['business_id'],
            $scope['effective_branch_id'] ?? 'all',
            $widget,
            $filter_fingerprint
        );
    }

    // ---- Order-level widgets (OrderService/PurchaseService reuse - no
    // product/category/brand dimension, since those services filter at the
    // order grain, not the line-item grain; see dev docs for the exact
    // filter applicability matrix per widget) ----

    protected function salesOverview(array $scope): array
    {
        $fetch = fn (Carbon $start, Carbon $end) => $this->salesSummary($scope, $start, $end);

        $result = !empty($scope['compare_previous_period'])
            ? $this->period_comparison->compare($scope['start_date'], $scope['end_date'], $fetch)
            : ['current' => $fetch($scope['start_date'], $scope['end_date']), 'previous' => null, 'deltas' => []];

        $result['daily_trend'] = $this->order_service->getDailyTrend($this->orderServiceObj($scope, $scope['start_date'], $scope['end_date']));

        return $result;
    }

    protected function salesSummary(array $scope, Carbon $start, Carbon $end): array
    {
        $summary = $this->order_service->getDashboardSummary($this->orderServiceObj($scope, $start, $end));

        $total_returns = $this->totalReturns($scope, $start, $end);
        $net_sales = round(($summary['total_sales'] ?? 0) - $total_returns, 2);
        $average_order_value = ($summary['total_orders'] ?? 0) > 0
            ? round($summary['total_sales'] / $summary['total_orders'], 2)
            : 0.0;

        return [
            'total_sales' => (float) ($summary['total_sales'] ?? 0),
            'net_sales' => $net_sales,
            'total_returns' => $total_returns,
            'total_orders' => (int) ($summary['total_orders'] ?? 0),
            'average_order_value' => $average_order_value,
            'total_paid' => (float) ($summary['total_paid'] ?? 0),
            'total_due' => (float) ($summary['total_due'] ?? 0),
        ];
    }

    /**
     * Sum of approved order returns for the range - the one small aggregate
     * genuinely missing anywhere else (OrderReturnService has no dashboard
     * summary method), needed so "Net Sales" isn't just an alias for the
     * already-shown "Total Sales" KPI.
     */
    protected function totalReturns(array $scope, Carbon $start, Carbon $end): float
    {
        $query = OrderReturn::query()
            ->where('is_deleted', 0)
            ->where('status', 'approved')
            ->where('business_id', $scope['business_id'])
            ->where('order_return_date', '>=', $start->copy()->startOfDay())
            ->where('order_return_date', '<=', $end->copy()->endOfDay());

        if (!empty($scope['effective_branch_id'])) {
            $query->where('branch_id', $scope['effective_branch_id']);
        }

        return round((float) $query->sum('total'), 2);
    }

    protected function purchasesOverview(array $scope): array
    {
        $fetch = function (Carbon $start, Carbon $end) use ($scope) {
            $obj = [
                'business_id' => $scope['business_id'],
                'branch_id' => $scope['effective_branch_id'],
                'start_date' => $start,
                'end_date' => $end,
                'allow_roles' => DashboardAccessService::TIER1_ROLES,
            ];

            return $this->purchase_service->getDashboardSummary($obj);
        };

        $result = !empty($scope['compare_previous_period'])
            ? $this->period_comparison->compare($scope['start_date'], $scope['end_date'], $fetch)
            : ['current' => $fetch($scope['start_date'], $scope['end_date']), 'previous' => null, 'deltas' => []];

        $result['daily_trend'] = $this->purchase_service->getDailyTrend([
            'business_id' => $scope['business_id'],
            'branch_id' => $scope['effective_branch_id'],
            'start_date' => $scope['start_date'],
            'end_date' => $scope['end_date'],
            'allow_roles' => DashboardAccessService::TIER1_ROLES,
        ]);

        return $result;
    }

    protected function inventorySummary(array $scope): array
    {
        return $this->inventory_service->build($scope);
    }

    protected function financeSummary(array $scope): array
    {
        if (empty($scope['is_finance'])) {
            return [];
        }

        return $this->finance_service->build($scope);
    }

    // ---- Line-item / dimension widgets (Report service reuse) ----

    protected function topProducts(array $scope): array
    {
        return $this->top_selling_service->build($this->reportObj($scope, ['limit' => 10]))->all();
    }

    protected function topCustomers(array $scope): array
    {
        return $this->customer_sales_service->build($this->reportObj($scope))
            ->sortByDesc('net')->take(10)->values()->all();
    }

    protected function branchComparison(array $scope): array
    {
        return $this->branch_sales_service->build($this->reportObj($scope))->all();
    }

    protected function orderSourceBreakdown(array $scope): array
    {
        return $this->order_source_sales_service->build($this->reportObj($scope))->all();
    }

    protected function paymentMethodBreakdown(array $scope): array
    {
        return $this->payment_method_sales_service->build($this->reportObj($scope))->all();
    }

    protected function loyaltySummary(array $scope): array
    {
        $rows = $this->loyalty_report_service->build($this->reportObj($scope));

        return [
            'points_used' => (float) $rows->sum('loyalty_points_used'),
            'points_earned' => (float) $rows->sum('loyalty_points_earned'),
            'discount_given' => round((float) $rows->sum('loyalty_discount_amount'), 2),
            'order_count' => $rows->count(),
        ];
    }

    // ---- New derived metrics ----

    protected function productMargin(array $scope): array
    {
        return $this->product_margin_service->build($this->reportObj($scope))->take(20)->values()->all();
    }

    protected function customerSegments(array $scope): array
    {
        return $this->customer_segment_service->build([
            'business_id' => $scope['business_id'],
            'branch_id' => $scope['effective_branch_id'],
            'start_date' => $scope['start_date'],
            'end_date' => $scope['end_date'],
            'user_id' => $scope['customer_id'] ?? null,
        ]);
    }

    protected function slowMoving(array $scope): array
    {
        return $this->slow_moving_service->build($this->reportObj($scope))->take(25)->values()->all();
    }

    /**
     * DataTables / drill-down payload for tabular widgets. Chart/KPI-only
     * widgets return an empty data set (the front-end never asks for them).
     */
    public function tableData(string $widget, array $scope): array
    {
        abort_unless(in_array($widget, self::WIDGETS, true), 404);

        $rows = match ($widget) {
            'top-products', 'top-customers', 'branch-comparison',
            'order-source-breakdown', 'payment-method-breakdown',
            'product-margin', 'slow-moving' => $this->widgetData($widget, $scope),
            'customer-segments' => $this->segmentRows($this->widgetData($widget, $scope)),
            default => [],
        };

        return ['data' => array_values(array_map(
            fn ($row) => is_object($row) ? (array) $row : $row,
            $rows
        ))];
    }

    /**
     * Excel export pieces for a tabular widget: headings + collection of
     * plain arrays. Used by AnalyticsWidgetExport so one generic export
     * class covers every widget instead of 6+ near-duplicates.
     *
     * @return array{headings: array<int, string>, rows: \Illuminate\Support\Collection}
     */
    public function exportPayload(string $widget, array $scope): array
    {
        abort_unless(in_array($widget, self::WIDGETS, true), 404);

        $table = $this->tableData($widget, $scope)['data'];
        $collection = collect($table);

        return match ($widget) {
            'top-products' => [
                'headings' => ['Product', 'Variation', 'SKU', 'Qty Sold', 'Net Sales'],
                'rows' => $collection->map(fn ($r) => [
                    $r['product_name'] ?? '',
                    $r['variation_name'] ?? '',
                    $r['sku'] ?? '',
                    $r['total_qty'] ?? $r['qty_sold'] ?? 0,
                    $r['net'] ?? $r['total_revenue'] ?? 0,
                ]),
            ],
            'top-customers' => [
                'headings' => ['Customer', 'Orders', 'Net Sales'],
                'rows' => $collection->map(fn ($r) => [
                    $r['customer'] ?? $r['customer_name'] ?? $r['name'] ?? '',
                    $r['order_count'] ?? $r['orders'] ?? 0,
                    $r['net'] ?? $r['total'] ?? 0,
                ]),
            ],
            'branch-comparison' => [
                'headings' => ['Branch', 'Orders', 'Net Sales'],
                'rows' => $collection->map(fn ($r) => [
                    $r['branch'] ?? $r['branch_name'] ?? $r['name'] ?? '',
                    $r['order_count'] ?? $r['orders'] ?? 0,
                    $r['net'] ?? $r['total'] ?? 0,
                ]),
            ],
            'order-source-breakdown' => [
                'headings' => ['Order Source', 'Orders', 'Net Sales'],
                'rows' => $collection->map(fn ($r) => [
                    $r['order_source'] ?? $r['order_source_name'] ?? $r['name'] ?? '',
                    $r['order_count'] ?? $r['orders'] ?? 0,
                    $r['net'] ?? $r['total'] ?? 0,
                ]),
            ],
            'payment-method-breakdown' => [
                'headings' => ['Payment Method', 'Orders', 'Net Sales'],
                'rows' => $collection->map(fn ($r) => [
                    $r['payment_method'] ?? $r['payment_method_name'] ?? $r['name'] ?? '',
                    $r['order_count'] ?? $r['orders'] ?? 0,
                    $r['net'] ?? $r['total'] ?? $r['total_amount'] ?? $r['amount'] ?? 0,
                ]),
            ],
            'product-margin' => [
                'headings' => ['Product', 'Qty Sold', 'Net Revenue', 'Est. COGS', 'Est. Margin', 'Est. Margin %'],
                'rows' => $collection->map(fn ($r) => [
                    $r['product_name'] ?? '',
                    $r['qty_sold'] ?? 0,
                    $r['net_revenue'] ?? 0,
                    $r['estimated_cogs'] ?? 0,
                    $r['estimated_margin'] ?? 0,
                    $r['estimated_margin_pct'] ?? null,
                ]),
            ],
            'slow-moving' => [
                'headings' => ['Product', 'Warehouse', 'Qty On Hand', 'Days Idle', 'Movement Class'],
                'rows' => $collection->map(fn ($r) => [
                    $r['product_name'] ?? '',
                    $r['warehouse_name'] ?? '',
                    $r['qty'] ?? $r['quantity'] ?? 0,
                    $r['days_idle'] ?? 0,
                    $r['movement_class_label'] ?? $r['movement_class'] ?? '',
                ]),
            ],
            'customer-segments' => [
                'headings' => ['Segment', 'Orders', 'Revenue'],
                'rows' => $collection->map(fn ($r) => [
                    $r['segment'] ?? '',
                    $r['order_count'] ?? 0,
                    $r['revenue'] ?? 0,
                ]),
            ],
            default => [
                'headings' => ['Key', 'Value'],
                'rows' => collect($this->widgetData($widget, $scope))->map(
                    fn ($value, $key) => [$key, is_scalar($value) ? $value : json_encode($value)]
                )->values(),
            ],
        };
    }

    protected function segmentRows(array $segments): array
    {
        $out = [];
        foreach (['new', 'returning', 'walkin'] as $key) {
            $out[] = [
                'segment' => $key,
                'order_count' => $segments[$key]['order_count'] ?? 0,
                'revenue' => $segments[$key]['revenue'] ?? 0,
            ];
        }

        return $out;
    }

    // ---- Shared obj-shape builders ----

    protected function orderServiceObj(array $scope, Carbon $start, Carbon $end): array
    {
        return [
            'business_id' => $scope['business_id'],
            'branch_id' => $scope['effective_branch_id'],
            'start_date' => $start,
            'end_date' => $end,
            'order_type_id' => $scope['order_type_id'] ?? null,
            'order_source_id' => $scope['order_source_id'] ?? null,
            'payment_method_id' => $scope['payment_method_id'] ?? null,
            'customer_id' => $scope['customer_id'] ?? null,
            'allow_roles' => DashboardAccessService::TIER1_ROLES,
        ];
    }

    /**
     * Shape expected by every Reports\Orders\* / Reports\Inventory\* service
     * reused here (business/branch/date/order_source/product/category/brand/
     * customer). Extra keys a given service doesn't read (e.g. category_id
     * on TopSellingReportService, which has no category column filter) are
     * silently ignored by that service's own filter method - harmless.
     */
    protected function reportObj(array $scope, array $extra = []): array
    {
        return array_merge([
            'business_id' => $scope['business_id'],
            'branch_id' => $scope['effective_branch_id'],
            'start_date' => $scope['start_date']->format('Y-m-d'),
            'end_date' => $scope['end_date']->format('Y-m-d'),
            'order_source_id' => $scope['order_source_id'] ?? null,
            'product_id' => $scope['product_id'] ?? null,
            'category_id' => $scope['category_id'] ?? null,
            'brand_id' => $scope['brand_id'] ?? null,
            'user_id' => $scope['customer_id'] ?? null,
        ], $extra);
    }
}
