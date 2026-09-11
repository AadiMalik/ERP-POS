<?php

namespace App\Services\Concrete\Admin\Reports\BusinessSummary\Providers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryContext;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryInsightFactory as Insight;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OperationsProvider
{
    public function collect(BusinessSummaryContext $context): array
    {
        if (!$context->hasModule('pos')) {
            return ['kpis' => [], 'insights' => [], 'charts' => []];
        }

        $current = $this->commercialTotals($context->filter_obj);
        $previous = $this->commercialTotals($context->previous_filter_obj);
        $complimentary = $this->complimentaryBreakdown($context);
        $discountByBranch = $this->discountByDimension($context, 'orders.branch_id', 'branches', 'branches.branch_id', 'branches.name');
        $discountBySource = $this->discountByDimension($context, 'orders.order_source_id', 'order_sources', 'order_sources.order_source_id', 'order_sources.name');

        $discountDelta = Insight::percentChange($current['discount'], $previous['discount']);
        $voucherDelta = Insight::percentChange($current['voucher'], $previous['voucher']);
        $compDelta = Insight::percentChange($current['complimentary_retail'], $previous['complimentary_retail']);
        $discountOfSales = $current['net'] > 0 ? round(($current['discount'] / $current['net']) * 100, 1) : 0;
        $compOfSales = $current['net'] > 0 ? round(($current['complimentary_retail'] / $current['net']) * 100, 1) : 0;

        $kpis = [
            'discount' => $current['discount'],
            'voucher' => $current['voucher'],
            'complimentary_retail' => $current['complimentary_retail'],
            'complimentary_cost' => $current['complimentary_cost'],
        ];

        $charts = [
            'discount_voucher' => [
                'labels' => [
                    __('reports.business_summary.chart_discounts'),
                    __('reports.business_summary.chart_vouchers'),
                    __('reports.business_summary.chart_complimentary'),
                ],
                'series' => [
                    round($current['discount'], 2),
                    round($current['voucher'], 2),
                    round($current['complimentary_retail'], 2),
                ],
            ],
        ];

        $insights = [];
        $discountChangeThreshold = (float) $context->threshold('discount_change_threshold_percent', 5);
        $highDiscountThreshold = (float) $context->threshold('high_discount_percent_threshold', 15);

        if ($current['discount'] > 0 || $current['discounted_orders'] > 0) {
            $severity = 'informational';
            if ($discountOfSales >= $highDiscountThreshold) {
                $severity = 'critical';
            } elseif ($discountDelta !== null && abs($discountDelta) >= $discountChangeThreshold) {
                $severity = $discountDelta > 0 ? 'important' : 'good';
            }

            $insights[] = Insight::make([
                'id' => 'discounts',
                'module' => 'sales',
                'severity' => $severity,
                'icon' => 'fa-percent',
                'title_key' => 'reports.business_summary.discounts_title',
                'title_params' => ['amount' => currency($current['discount'])],
                'description_key' => 'reports.business_summary.discounts_desc',
                'description_params' => [
                    'orders' => $current['discounted_orders'],
                    'percent' => Insight::formatMetric($discountOfSales, 'percent'),
                    'delta' => $this->deltaLabel($discountDelta),
                    'average' => $current['discounted_orders'] > 0
                        ? currency($current['discount'] / $current['discounted_orders'])
                        : currency(0),
                ],
                'metric' => $current['discount'],
                'metric_type' => 'money',
                'value' => $current['discount'],
                'delta_percent' => $discountDelta,
                'impact' => $current['discount'],
                'details' => array_merge($discountByBranch, $discountBySource),
                'why_key' => $this->discountWhyKey($discountDelta, $discountOfSales, $discountChangeThreshold, $highDiscountThreshold),
                'why_params' => ['threshold' => Insight::formatMetric($discountChangeThreshold, 'percent')],
                'action' => Insight::action($context, 'reports.business_summary.actions.view_discounts', '/admin/reports/discount-report', [], 'reports.discount-report.view'),
            ]);
        }

        $voucherChangeThreshold = (float) $context->threshold('voucher_change_threshold_percent', 5);
        if ($current['voucher'] > 0 || $current['voucher_orders'] > 0) {
            $severity = 'informational';
            if ($voucherDelta !== null && abs($voucherDelta) >= $voucherChangeThreshold) {
                $severity = $voucherDelta > 0 ? 'important' : 'good';
            }

            $insights[] = Insight::make([
                'id' => 'vouchers',
                'module' => 'sales',
                'severity' => $severity,
                'icon' => 'fa-ticket',
                'title_key' => 'reports.business_summary.vouchers_title',
                'title_params' => ['amount' => currency($current['voucher'])],
                'description_key' => 'reports.business_summary.vouchers_desc',
                'description_params' => [
                    'count' => $current['voucher_orders'],
                    'delta' => $this->deltaLabel($voucherDelta),
                ],
                'metric' => $current['voucher'],
                'metric_type' => 'money',
                'value' => $current['voucher'],
                'delta_percent' => $voucherDelta,
                'impact' => $current['voucher'],
                'why_key' => ($voucherDelta !== null && $voucherDelta >= $voucherChangeThreshold)
                    ? 'reports.business_summary.why.voucher_increase'
                    : (($voucherDelta !== null && $voucherDelta <= -$voucherChangeThreshold)
                        ? 'reports.business_summary.why.voucher_decrease'
                        : null),
                'why_params' => ['threshold' => Insight::formatMetric($voucherChangeThreshold, 'percent')],
                'action' => Insight::action($context, 'reports.business_summary.actions.view_vouchers', '/admin/reports/voucher-usage-report', [], 'reports.voucher-usage.view'),
            ]);
        }

        $compThreshold = (float) $context->threshold('complimentary_sales_percent_threshold', 5);
        $compCount = $current['full_complimentary'] + $current['partial_complimentary'];
        if ($compCount > 0 || $current['complimentary_retail'] > 0) {
            $severity = 'informational';
            if ($compOfSales >= $compThreshold) {
                $severity = 'critical';
            } elseif ($compDelta !== null && $compDelta >= $compThreshold) {
                $severity = 'important';
            }

            $canCost = Auth::user()?->can('order.complimentary.view-cost');
            $descParams = [
                'orders' => $compCount,
                'full' => $current['full_complimentary'],
                'partial' => $current['partial_complimentary'],
                'retail' => currency($current['complimentary_retail']),
                'qty' => $complimentary['qty'],
                'delta' => $this->deltaLabel($compDelta),
            ];
            if ($canCost) {
                $descParams['cost'] = currency($current['complimentary_cost']);
            }

            $insights[] = Insight::make([
                'id' => 'complimentary',
                'module' => 'sales',
                'severity' => $severity,
                'icon' => 'fa-gift',
                'title_key' => 'reports.business_summary.complimentary_title',
                'title_params' => ['amount' => currency($current['complimentary_retail'])],
                'description_key' => $canCost
                    ? 'reports.business_summary.complimentary_desc_with_cost'
                    : 'reports.business_summary.complimentary_desc',
                'description_params' => $descParams,
                'metric' => $current['complimentary_retail'],
                'metric_type' => 'money',
                'value' => $current['complimentary_retail'],
                'delta_percent' => $compDelta,
                'impact' => $current['complimentary_retail'],
                'details' => $complimentary['reasons'],
                'why_key' => $compOfSales >= $compThreshold
                    ? 'reports.business_summary.why.high_complimentary'
                    : null,
                'why_params' => ['threshold' => Insight::formatMetric($compThreshold, 'percent')],
                'action' => Insight::action($context, 'reports.business_summary.actions.view_complimentary', '/admin/reports/complimentary-report', [], 'reports.complimentary-report.view'),
            ]);
        }

        return compact('kpis', 'insights', 'charts');
    }

    protected function commercialTotals(array $obj): array
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

        $row = $query
            ->selectRaw('COALESCE(SUM(total), 0) as net')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount')
            ->selectRaw('COALESCE(SUM(voucher_discount_amount), 0) as voucher')
            ->selectRaw('COALESCE(SUM(complimentary_retail_value), 0) as complimentary_retail')
            ->selectRaw('COALESCE(SUM(complimentary_cost), 0) as complimentary_cost')
            ->selectRaw("SUM(CASE WHEN complimentary_status IN ('full','partial') THEN 1 ELSE 0 END) as complimentary_orders")
            ->selectRaw("SUM(CASE WHEN complimentary_status = 'full' THEN 1 ELSE 0 END) as full_complimentary")
            ->selectRaw("SUM(CASE WHEN complimentary_status = 'partial' THEN 1 ELSE 0 END) as partial_complimentary")
            ->selectRaw('SUM(CASE WHEN discount_amount > 0 THEN 1 ELSE 0 END) as discounted_orders')
            ->selectRaw('SUM(CASE WHEN voucher_id IS NOT NULL THEN 1 ELSE 0 END) as voucher_orders')
            ->first();

        return [
            'net' => (float) ($row->net ?? 0),
            'discount' => (float) ($row->discount ?? 0),
            'voucher' => (float) ($row->voucher ?? 0),
            'complimentary_retail' => (float) ($row->complimentary_retail ?? 0),
            'complimentary_cost' => (float) ($row->complimentary_cost ?? 0),
            'full_complimentary' => (int) ($row->full_complimentary ?? 0),
            'partial_complimentary' => (int) ($row->partial_complimentary ?? 0),
            'discounted_orders' => (int) ($row->discounted_orders ?? 0),
            'voucher_orders' => (int) ($row->voucher_orders ?? 0),
        ];
    }

    protected function complimentaryBreakdown(BusinessSummaryContext $context): array
    {
        $query = OrderDetail::query()
            ->join('orders', 'orders.order_id', '=', 'order_details.order_id')
            ->leftJoin('complimentary_reasons as line_reasons', 'line_reasons.complimentary_reason_id', '=', 'order_details.complimentary_reason_id')
            ->leftJoin('complimentary_reasons as order_reasons', 'order_reasons.complimentary_reason_id', '=', 'orders.complimentary_reason_id')
            ->where('order_details.is_complimentary', 1)
            ->where('orders.status', 'posted')
            ->where('orders.is_deleted', 0)
            ->where('orders.business_id', $context->business_id)
            ->when($context->branch_id, fn ($q) => $q->where('orders.branch_id', $context->branch_id))
            ->where('orders.sale_date', '>=', businessStartOfDay($context->start_date))
            ->where('orders.sale_date', '<=', businessEndOfDay($context->end_date));

        $qty = (float) (clone $query)->sum('order_details.quantity');

        $reasons = (clone $query)
            ->selectRaw("COALESCE(NULLIF(line_reasons.name, ''), NULLIF(order_reasons.name, ''), ?) as reason", [__('reports.business_summary.unknown')])
            ->selectRaw('SUM(order_details.quantity) as qty')
            ->selectRaw('SUM(order_details.complimentary_value) as retail')
            ->groupBy('reason')
            ->orderByDesc('retail')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->reason,
                'value' => currency($row->retail) . ' · ' . $row->qty,
            ])
            ->all();

        return ['qty' => $qty, 'reasons' => $reasons];
    }

    protected function discountByDimension(BusinessSummaryContext $context, string $groupCol, string $table, string $joinCol, string $nameCol): array
    {
        return Order::query()
            ->leftJoin($table, $joinCol, '=', $groupCol)
            ->where('orders.is_deleted', 0)
            ->where('orders.status', 'posted')
            ->where('orders.business_id', $context->business_id)
            ->when($context->branch_id, fn ($q) => $q->where('orders.branch_id', $context->branch_id))
            ->where('orders.sale_date', '>=', businessStartOfDay($context->start_date))
            ->where('orders.sale_date', '<=', businessEndOfDay($context->end_date))
            ->where('orders.discount_amount', '>', 0)
            ->selectRaw($nameCol . ' as label')
            ->selectRaw('SUM(orders.discount_amount) as amount')
            ->groupBy($nameCol)
            ->orderByDesc('amount')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label ?: __('reports.business_summary.unknown'),
                'value' => currency($row->amount),
            ])
            ->all();
    }

    protected function discountWhyKey(?float $delta, float $ofSales, float $changeThreshold, float $highThreshold): ?string
    {
        if ($ofSales >= $highThreshold) {
            return 'reports.business_summary.why.high_discount_share';
        }
        if ($delta !== null && $delta >= $changeThreshold) {
            return 'reports.business_summary.why.discount_increase';
        }
        if ($delta !== null && $delta <= -$changeThreshold) {
            return 'reports.business_summary.why.discount_decrease';
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
}
