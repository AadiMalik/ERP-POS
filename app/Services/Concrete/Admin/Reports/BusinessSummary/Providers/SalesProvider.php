<?php

namespace App\Services\Concrete\Admin\Reports\BusinessSummary\Providers;

use App\Enums\Status;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\OrderSource;
use App\Models\OrderType;
use App\Models\PosDevice;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryContext;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryInsightFactory as Insight;
use App\Services\Concrete\Admin\Reports\Orders\BranchSalesReportService;
use App\Services\Concrete\Admin\Reports\Orders\OrderSourceSalesReportService;
use App\Services\Concrete\Admin\Reports\Orders\TopSellingReportService;
use Illuminate\Support\Facades\DB;

class SalesProvider
{
    public function __construct(
        protected BranchSalesReportService $branch_sales_service,
        protected OrderSourceSalesReportService $source_sales_service,
        protected TopSellingReportService $top_selling_service
    ) {
    }

    public function collect(BusinessSummaryContext $context): array
    {
        if (!$context->hasModule('pos') || !$context->canAny([
            'reports.sales-report.view',
            'reports.branch-sales.view',
            'reports.order-source-sales.view',
            'order.view',
        ])) {
            return ['kpis' => [], 'charts' => [], 'insights' => []];
        }

        $current = $this->orderTotals($context->filter_obj);
        $previous = $this->orderTotals($context->previous_filter_obj);
        $branches = $context->can('reports.branch-sales.view')
            ? $this->branch_sales_service->build($context->filter_obj)
            : collect();
        $previousBranches = $branches->isNotEmpty()
            ? $this->branch_sales_service->build($context->previous_filter_obj)->keyBy('branch_id')
            : collect();
        $sources = $context->can('reports.order-source-sales.view')
            ? $this->source_sales_service->build($context->filter_obj)
            : collect();
        $previousSources = $sources->isNotEmpty()
            ? $this->source_sales_service->build($context->previous_filter_obj)->keyBy('order_source_id')
            : collect();
        $orderTypes = $this->orderTypeTotals($context->filter_obj);
        $previousTypes = $this->orderTypeTotals($context->previous_filter_obj)->keyBy('order_type_id');
        $cancelled = $this->statusTotals($context->filter_obj, ['cancelled', 'void']);
        $previousCancelled = $this->statusTotals($context->previous_filter_obj, ['cancelled', 'void']);
        $returns = $this->returnTotals($context);
        $stuck = $this->stuckOrders($context);
        $offline = $this->offlineSync($context);

        $salesDelta = Insight::percentChange($current['net'], $previous['net']);
        $orderDelta = Insight::percentChange($current['order_count'], $previous['order_count']);
        $aov = $current['order_count'] > 0 ? $current['net'] / $current['order_count'] : 0;
        $cancelRate = $this->rate($cancelled['count'], $current['order_count'] + $cancelled['count']);
        $returnRate = $this->rate($returns['count'], $current['order_count']);

        $kpis = [
            'sales' => $current['net'],
            'orders' => $current['order_count'],
            'aov' => $aov,
            'paid' => $current['paid'],
            'due' => max($current['net'] - $current['paid'], 0),
            'discount' => $current['discount'],
            'voucher' => $current['voucher'],
            'tax' => $current['tax'],
            'complimentary_retail' => $current['complimentary_retail'],
            'sales_delta' => $salesDelta,
            'order_delta' => $orderDelta,
        ];

        $sourceCodes = $this->sourceCodes();
        $channelChart = $sources->map(fn ($row) => [
            'id' => $row->order_source_id,
            'code' => $sourceCodes[$row->order_source_id] ?? '',
            'label' => $row->order_source,
            'orders' => $row->order_count,
            'net' => $row->net,
            'aov' => $row->order_count > 0 ? $row->net / $row->order_count : 0,
            'delta' => Insight::percentChange($row->net, optional($previousSources->get($row->order_source_id))->net ?? 0),
        ])->values()->all();

        $charts = [
            'sales_by_branch' => [
                'labels' => $branches->pluck('branch')->all(),
                'series' => $branches->pluck('net')->map(fn ($v) => round((float) $v, 2))->all(),
            ],
            'sales_by_channel' => [
                'labels' => $sources->pluck('order_source')->all(),
                'series' => $sources->pluck('net')->map(fn ($v) => round((float) $v, 2))->all(),
            ],
            'order_types' => [
                'labels' => $orderTypes->pluck('order_type')->all(),
                'series' => $orderTypes->pluck('net')->map(fn ($v) => round((float) $v, 2))->all(),
            ],
        ];

        $insights = [];

        if ($current['order_count'] === 0 && $cancelled['count'] === 0) {
            $insights[] = Insight::make([
                'id' => 'sales_no_activity',
                'module' => 'sales',
                'severity' => 'informational',
                'icon' => 'fa-receipt',
                'title_key' => 'reports.business_summary.sales_no_activity_title',
                'description_key' => 'reports.business_summary.no_activity_period',
                'empty' => true,
                'action' => Insight::action($context, 'reports.business_summary.actions.view_orders', '/admin/order', [], 'order.view'),
            ]);
        } else {
            $insights[] = Insight::make([
                'id' => 'sales_overview',
                'module' => 'sales',
                'severity' => $this->salesSeverity($salesDelta, $context),
                'icon' => 'fa-chart-line',
                'title_key' => 'reports.business_summary.sales_overview_title',
                'title_params' => ['amount' => currency($current['net']), 'count' => $current['order_count']],
                'description_key' => 'reports.business_summary.sales_overview_desc',
                'description_params' => [
                    'paid' => currency($current['paid']),
                    'due' => currency(max($current['net'] - $current['paid'], 0)),
                    'aov' => currency($aov),
                    'delta' => $this->deltaLabel($salesDelta),
                ],
                'metric' => $current['net'],
                'metric_type' => 'money',
                'value' => $current['net'],
                'impact' => $current['net'],
                'delta_percent' => $salesDelta,
                'details' => $this->branchDetails($branches, $previousBranches),
                'action' => Insight::action($context, 'reports.business_summary.actions.view_sales_report', '/admin/reports/sales-report', [], 'reports.sales-report.view'),
                'why_key' => $this->salesWhyKey($salesDelta, $context),
            ]);
        }

        foreach ($this->branchInsights($context, $branches, $previousBranches) as $insight) {
            $insights[] = $insight;
        }

        foreach ($this->channelInsights($context, $channelChart, $sourceCodes) as $insight) {
            $insights[] = $insight;
        }

        foreach ($this->orderTypeInsights($context, $orderTypes, $previousTypes) as $insight) {
            $insights[] = $insight;
        }

        foreach ($this->topProductInsights($context) as $insight) {
            $insights[] = $insight;
        }

        $cancelThreshold = (float) $context->threshold('high_cancellation_rate_percent', 10);
        if ($cancelled['count'] > 0) {
            $insights[] = Insight::make([
                'id' => 'cancellations',
                'module' => 'sales',
                'severity' => $cancelRate >= $cancelThreshold ? 'critical' : 'informational',
                'icon' => 'fa-ban',
                'title_key' => 'reports.business_summary.cancellations_title',
                'title_params' => ['count' => $cancelled['count']],
                'description_key' => 'reports.business_summary.cancellations_desc',
                'description_params' => [
                    'amount' => currency($cancelled['total']),
                    'rate' => Insight::formatMetric($cancelRate, 'percent'),
                    'delta' => $this->deltaLabel(Insight::percentChange($cancelled['count'], $previousCancelled['count'])),
                ],
                'metric' => $cancelled['count'],
                'value' => $cancelled['total'],
                'impact' => $cancelled['total'],
                'why_key' => $cancelRate >= $cancelThreshold ? 'reports.business_summary.why.high_cancellation_rate' : null,
                'why_params' => ['threshold' => Insight::formatMetric($cancelThreshold, 'percent')],
                'action' => Insight::action($context, 'reports.business_summary.actions.view_cancelled', '/admin/reports/cancelled-orders', [], 'reports.cancelled-orders.view'),
            ]);
        }

        $returnThreshold = (float) $context->threshold('high_return_rate_percent', 10);
        if ($returns['count'] > 0) {
            $insights[] = Insight::make([
                'id' => 'returns',
                'module' => 'sales',
                'severity' => $returnRate >= $returnThreshold ? 'important' : 'informational',
                'icon' => 'fa-rotate-left',
                'title_key' => 'reports.business_summary.returns_title',
                'title_params' => ['count' => $returns['count']],
                'description_key' => 'reports.business_summary.returns_desc',
                'description_params' => [
                    'amount' => currency($returns['total']),
                    'rate' => Insight::formatMetric($returnRate, 'percent'),
                ],
                'metric' => $returns['count'],
                'value' => $returns['total'],
                'impact' => $returns['total'],
                'why_key' => $returnRate >= $returnThreshold ? 'reports.business_summary.why.high_return_rate' : null,
                'why_params' => ['threshold' => Insight::formatMetric($returnThreshold, 'percent')],
                'action' => Insight::action($context, 'reports.business_summary.actions.view_returns', '/admin/order-return', [], 'order-return.view'),
            ]);
        }

        if ($stuck['count'] > 0) {
            $insights[] = Insight::make([
                'id' => 'stuck_orders',
                'module' => 'sales',
                'severity' => 'important',
                'icon' => 'fa-clock',
                'title_key' => 'reports.business_summary.stuck_orders_title',
                'title_params' => ['count' => $stuck['count']],
                'description_key' => 'reports.business_summary.stuck_orders_desc',
                'description_params' => ['hours' => $context->threshold('delayed_order_hours', 24)],
                'metric' => $stuck['count'],
                'impact' => $stuck['count'],
                'why_key' => 'reports.business_summary.why.stuck_orders',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_orders', '/admin/order', ['status' => 'hold'], 'order.view'),
            ]);
        }

        foreach ($offline as $insight) {
            $insights[] = $insight;
        }

        return compact('kpis', 'charts', 'insights');
    }

    protected function orderTotals(array $obj): array
    {
        $row = $this->postedOrders($obj)
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as gross')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount')
            ->selectRaw('COALESCE(SUM(voucher_discount_amount), 0) as voucher')
            ->selectRaw('COALESCE(SUM(loyalty_discount_amount), 0) as loyalty')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as tax')
            ->selectRaw('COALESCE(SUM(delivery_charge), 0) as delivery')
            ->selectRaw('COALESCE(SUM(total), 0) as net')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as paid')
            ->selectRaw('COALESCE(SUM(complimentary_retail_value), 0) as complimentary_retail')
            ->selectRaw('COALESCE(SUM(complimentary_cost), 0) as complimentary_cost')
            ->selectRaw("SUM(CASE WHEN complimentary_status = 'full' THEN 1 ELSE 0 END) as full_complimentary")
            ->selectRaw("SUM(CASE WHEN complimentary_status = 'partial' THEN 1 ELSE 0 END) as partial_complimentary")
            ->selectRaw('SUM(CASE WHEN discount_amount > 0 THEN 1 ELSE 0 END) as discounted_orders')
            ->selectRaw('SUM(CASE WHEN voucher_id IS NOT NULL THEN 1 ELSE 0 END) as voucher_orders')
            ->first();

        return [
            'order_count' => (int) ($row->order_count ?? 0),
            'gross' => (float) ($row->gross ?? 0),
            'discount' => (float) ($row->discount ?? 0),
            'voucher' => (float) ($row->voucher ?? 0),
            'loyalty' => (float) ($row->loyalty ?? 0),
            'tax' => (float) ($row->tax ?? 0),
            'delivery' => (float) ($row->delivery ?? 0),
            'net' => (float) ($row->net ?? 0),
            'paid' => (float) ($row->paid ?? 0),
            'complimentary_retail' => (float) ($row->complimentary_retail ?? 0),
            'complimentary_cost' => (float) ($row->complimentary_cost ?? 0),
            'full_complimentary' => (int) ($row->full_complimentary ?? 0),
            'partial_complimentary' => (int) ($row->partial_complimentary ?? 0),
            'discounted_orders' => (int) ($row->discounted_orders ?? 0),
            'voucher_orders' => (int) ($row->voucher_orders ?? 0),
        ];
    }

    protected function postedOrders(array $obj)
    {
        $query = Order::query()->where('is_deleted', 0)->where('status', 'posted');

        if (!empty($obj['business_id'])) {
            $query->where('business_id', $obj['business_id']);
        }
        if (!empty($obj['branch_id'])) {
            $query->where('branch_id', $obj['branch_id']);
        }
        if (!empty($obj['start_date'])) {
            $query->where('sale_date', '>=', businessStartOfDay($obj['start_date']));
        }
        if (!empty($obj['end_date'])) {
            $query->where('sale_date', '<=', businessEndOfDay($obj['end_date']));
        }

        return $query;
    }

    protected function orderTypeTotals(array $obj)
    {
        $rows = $this->postedOrders($obj)
            ->select('order_type_id', DB::raw('COUNT(*) as order_count'), DB::raw('COALESCE(SUM(total), 0) as net'))
            ->groupBy('order_type_id')
            ->get()
            ->keyBy('order_type_id');

        $names = OrderType::whereIn('order_type_id', $rows->keys()->filter()->all())->pluck('name', 'order_type_id');

        return $rows->map(function ($row, $id) use ($names) {
            return (object) [
                'order_type_id' => $id,
                'order_type' => $names[$id] ?? __('reports.business_summary.unknown'),
                'order_count' => (int) $row->order_count,
                'net' => (float) $row->net,
            ];
        })->values();
    }

    protected function statusTotals(array $obj, array $statuses): array
    {
        $query = Order::query()->where('is_deleted', 0)->whereIn('status', $statuses);

        if (!empty($obj['business_id'])) {
            $query->where('business_id', $obj['business_id']);
        }
        if (!empty($obj['branch_id'])) {
            $query->where('branch_id', $obj['branch_id']);
        }
        if (!empty($obj['start_date'])) {
            $query->where('sale_date', '>=', businessStartOfDay($obj['start_date']));
        }
        if (!empty($obj['end_date'])) {
            $query->where('sale_date', '<=', businessEndOfDay($obj['end_date']));
        }

        $row = $query->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total), 0) as total')->first();

        return ['count' => (int) ($row->cnt ?? 0), 'total' => (float) ($row->total ?? 0)];
    }

    protected function returnTotals(BusinessSummaryContext $context): array
    {
        $query = OrderReturn::query()
            ->where('is_deleted', 0)
            ->where('status', Status::APPROVED)
            ->where('business_id', $context->business_id);

        if ($context->branch_id) {
            $query->where('branch_id', $context->branch_id);
        }

        $query->whereBetween('order_return_date', [
            businessStartOfDay($context->start_date),
            businessEndOfDay($context->end_date),
        ]);

        $row = $query->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total), 0) as total')->first();

        return ['count' => (int) ($row->cnt ?? 0), 'total' => (float) ($row->total ?? 0)];
    }

    protected function stuckOrders(BusinessSummaryContext $context): array
    {
        $hours = (int) $context->threshold('delayed_order_hours', 24);
        $cutoff = now()->subHours($hours);

        $count = Order::query()
            ->where('is_deleted', 0)
            ->where('business_id', $context->business_id)
            ->when($context->branch_id, fn ($q) => $q->where('branch_id', $context->branch_id))
            ->whereIn('status', ['draft', 'hold'])
            ->where('order_date', '<=', $cutoff)
            ->count();

        return ['count' => $count];
    }

    protected function offlineSync(BusinessSummaryContext $context): array
    {
        if (!$context->hasModule('pos')) {
            return [];
        }

        $hours = (int) $context->threshold('offline_sync_stale_hours', 24);
        $cutoff = now()->subHours($hours);

        $stale = PosDevice::query()
            ->where('is_deleted', 0)
            ->where('business_id', $context->business_id)
            ->when($context->branch_id, fn ($q) => $q->where('branch_id', $context->branch_id))
            ->where('status', Status::ACTIVE)
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_sync_at')->orWhere('last_sync_at', '<=', $cutoff);
            })
            ->count();

        if ($stale <= 0) {
            return [];
        }

        return [Insight::make([
            'id' => 'offline_sync_stale',
            'module' => 'pos',
            'severity' => 'critical',
            'icon' => 'fa-wifi',
            'title_key' => 'reports.business_summary.offline_sync_title',
            'title_params' => ['count' => $stale],
            'description_key' => 'reports.business_summary.offline_sync_desc',
            'description_params' => ['hours' => $hours],
            'metric' => $stale,
            'impact' => $stale,
            'why_key' => 'reports.business_summary.why.offline_sync',
            'action' => Insight::action($context, 'reports.business_summary.actions.view_offline_orders', '/admin/reports/offline-orders-report', [], 'reports.offline-orders-report.view'),
        ])];
    }

    protected function branchInsights(BusinessSummaryContext $context, $branches, $previousBranches): array
    {
        if ($branches->count() < 2) {
            return [];
        }

        $insights = [];
        $avg = $branches->avg('net') ?: 0;
        $best = $branches->sortByDesc('net')->first();
        $worst = $branches->sortBy('net')->first();

        if ($best && $avg > 0 && $best->net >= $avg * 1.5) {
            $insights[] = Insight::make([
                'id' => 'branch_strong_' . $best->branch_id,
                'module' => 'sales',
                'severity' => 'excellent',
                'icon' => 'fa-store',
                'title_key' => 'reports.business_summary.branch_strong_title',
                'title_params' => ['branch' => $best->branch],
                'description_key' => 'reports.business_summary.branch_strong_desc',
                'description_params' => [
                    'amount' => currency($best->net),
                    'orders' => $best->order_count,
                ],
                'metric' => $best->net,
                'metric_type' => 'money',
                'value' => $best->net,
                'impact' => $best->net,
                'action' => Insight::action($context, 'reports.business_summary.actions.view_branch_sales', '/admin/reports/branch-sales', [], 'reports.branch-sales.view'),
            ]);
        }

        if ($worst && $avg > 0 && $worst->net <= $avg * 0.5 && $worst->net >= 0) {
            $prev = optional($previousBranches->get($worst->branch_id))->net ?? 0;
            $insights[] = Insight::make([
                'id' => 'branch_low_' . $worst->branch_id,
                'module' => 'sales',
                'severity' => 'important',
                'icon' => 'fa-store-slash',
                'title_key' => 'reports.business_summary.branch_low_title',
                'title_params' => ['branch' => $worst->branch],
                'description_key' => 'reports.business_summary.branch_low_desc',
                'description_params' => [
                    'amount' => currency($worst->net),
                    'orders' => $worst->order_count,
                    'delta' => $this->deltaLabel(Insight::percentChange($worst->net, $prev)),
                ],
                'metric' => $worst->net,
                'metric_type' => 'money',
                'value' => $worst->net,
                'impact' => $avg - $worst->net,
                'why_key' => 'reports.business_summary.why.branch_low',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_branch_sales', '/admin/reports/branch-sales', [], 'reports.branch-sales.view'),
            ]);
        }

        return $insights;
    }

    protected function channelInsights(BusinessSummaryContext $context, array $channels, array $sourceCodes): array
    {
        if ($channels === []) {
            return [];
        }

        $insights = [];
        $threshold = (float) $context->threshold('discount_change_threshold_percent', 5);
        $routes = [
            'POS' => '/admin/order',
            'OFFLINE_POS' => '/admin/reports/offline-orders-report',
            'WEBSITE' => '/admin/order',
            'MOBILE_APP' => '/admin/order',
        ];

        foreach ($channels as $channel) {
            $code = $channel['code'] ?: 'UNKNOWN';
            $delta = $channel['delta'];
            $severity = 'informational';
            if ($delta !== null && $delta <= -$threshold && $channel['orders'] > 0) {
                $severity = 'important';
            } elseif ($delta !== null && $delta >= (float) $context->threshold('excellent_sales_growth_percent', 20) && $channel['net'] > 0) {
                $severity = 'good';
            }

            $path = $routes[$code] ?? '/admin/reports/order-source-sales';
            $permission = $code === 'OFFLINE_POS' ? 'reports.offline-orders-report.view' : 'reports.order-source-sales.view';

            $insights[] = Insight::make([
                'id' => 'channel_' . strtolower($code),
                'module' => 'sales',
                'severity' => $severity,
                'icon' => $this->channelIcon($code),
                'title_key' => 'reports.business_summary.channel_title',
                'title_params' => ['channel' => $channel['label']],
                'description_key' => 'reports.business_summary.channel_desc',
                'description_params' => [
                    'orders' => $channel['orders'],
                    'amount' => currency($channel['net']),
                    'aov' => currency($channel['aov']),
                    'delta' => $this->deltaLabel($delta),
                ],
                'metric' => $channel['net'],
                'metric_type' => 'money',
                'value' => $channel['net'],
                'delta_percent' => $delta,
                'impact' => $channel['net'],
                'why_key' => $severity === 'important' ? 'reports.business_summary.why.channel_decline' : null,
                'action' => Insight::action($context, 'reports.business_summary.actions.view_channel', $path, ['order_source_id' => $channel['id']], $permission),
            ]);
        }

        return $insights;
    }

    protected function orderTypeInsights(BusinessSummaryContext $context, $types, $previousTypes): array
    {
        if ($types->isEmpty()) {
            return [];
        }

        $details = $types->map(function ($row) use ($previousTypes) {
            $prev = optional($previousTypes->get($row->order_type_id))->net ?? 0;

            return [
                'label' => $row->order_type,
                'value' => currency($row->net),
                'extra' => $this->deltaLabel(Insight::percentChange($row->net, $prev)),
            ];
        })->all();

        $top = $types->sortByDesc('net')->first();

        return [Insight::make([
            'id' => 'order_types',
            'module' => 'sales',
            'severity' => 'informational',
            'icon' => 'fa-tags',
            'title_key' => 'reports.business_summary.order_types_title',
            'description_key' => 'reports.business_summary.order_types_desc',
            'description_params' => [
                'type' => $top->order_type,
                'amount' => currency($top->net),
                'orders' => $top->order_count,
            ],
            'metric' => $top->net,
            'metric_type' => 'money',
            'details' => $details,
            'action' => Insight::action($context, 'reports.business_summary.actions.view_orders', '/admin/order', [], 'order.view'),
        ])];
    }

    protected function topProductInsights(BusinessSummaryContext $context): array
    {
        if (!$context->can('reports.top-selling.view')) {
            return [];
        }

        $rows = $this->top_selling_service->build(array_merge($context->filter_obj, [
            'group_by' => 'product',
            'rank_by' => 'net',
            'limit' => 5,
        ]));

        if ($rows->isEmpty()) {
            return [];
        }

        $top = $rows->first();
        $details = $rows->map(fn ($row) => [
            'label' => $row->product_name ?: __('reports.business_summary.unknown'),
            'value' => currency($row->net),
            'extra' => $row->total_qty,
        ])->all();

        return [Insight::make([
            'id' => 'top_products',
            'module' => 'sales',
            'severity' => 'informational',
            'icon' => 'fa-trophy',
            'title_key' => 'reports.business_summary.top_products_title',
            'description_key' => 'reports.business_summary.top_products_desc',
            'description_params' => [
                'product' => $top->product_name ?: __('reports.business_summary.unknown'),
                'amount' => currency($top->net),
                'qty' => $top->total_qty,
            ],
            'metric' => $top->net,
            'metric_type' => 'money',
            'value' => $top->net,
            'details' => $details,
            'action' => Insight::action($context, 'reports.business_summary.actions.view_top_selling', '/admin/reports/top-selling', ['group_by' => 'product'], 'reports.top-selling.view'),
        ])];
    }

    protected function branchDetails($branches, $previousBranches): array
    {
        return $branches->sortByDesc('net')->take(8)->map(function ($row) use ($previousBranches) {
            $prev = optional($previousBranches->get($row->branch_id))->net ?? 0;

            return [
                'label' => $row->branch,
                'value' => currency($row->net),
                'extra' => $row->order_count . ' · ' . $this->deltaLabel(Insight::percentChange($row->net, $prev)),
            ];
        })->values()->all();
    }

    protected function sourceCodes(): array
    {
        return OrderSource::query()
            ->where('is_deleted', 0)
            ->pluck('code', 'order_source_id')
            ->all();
    }

    protected function salesSeverity(?float $delta, BusinessSummaryContext $context): string
    {
        $excellent = (float) $context->threshold('excellent_sales_growth_percent', 20);
        $warn = (float) $context->threshold('discount_change_threshold_percent', 5);

        if ($delta !== null && $delta >= $excellent) {
            return 'excellent';
        }
        if ($delta !== null && $delta >= $warn) {
            return 'good';
        }
        if ($delta !== null && $delta <= -$warn) {
            return 'important';
        }

        return 'informational';
    }

    protected function salesWhyKey(?float $delta, BusinessSummaryContext $context): ?string
    {
        $warn = (float) $context->threshold('discount_change_threshold_percent', 5);
        if ($delta !== null && $delta <= -$warn) {
            return 'reports.business_summary.why.sales_decline';
        }
        if ($delta !== null && $delta >= (float) $context->threshold('excellent_sales_growth_percent', 20)) {
            return 'reports.business_summary.why.sales_growth';
        }

        return null;
    }

    protected function deltaLabel(?float $delta): string
    {
        if ($delta === null) {
            return __('reports.business_summary.no_comparison');
        }

        $sign = $delta > 0 ? '+' : '';

        return $sign . Insight::formatMetric($delta, 'percent');
    }

    protected function rate(int $part, int $whole): float
    {
        if ($whole <= 0) {
            return 0.0;
        }

        return round(($part / $whole) * 100, 1);
    }

    protected function channelIcon(string $code): string
    {
        return match ($code) {
            'POS' => 'fa-cash-register',
            'OFFLINE_POS' => 'fa-laptop',
            'WEBSITE' => 'fa-globe',
            'MOBILE_APP' => 'fa-mobile-screen',
            default => 'fa-shop',
        };
    }
}
