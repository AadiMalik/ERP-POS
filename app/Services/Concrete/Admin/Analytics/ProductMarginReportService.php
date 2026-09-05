<?php

namespace App\Services\Concrete\Admin\Analytics;

use App\Models\OrderDetail;
use App\Models\OrderReturnDetail;
use App\Services\Concrete\Admin\Reports\Orders\BaseOrderReportService;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Per-product profit margin - the one genuinely new metric with no existing
 * report to reuse (order_details.cost_price is captured on every line but,
 * per resources/docs/developer/06-reports-infrastructure.md, was never
 * surfaced by any report before this). cost_price is a snapshot of
 * ProductVariationStock.avg_price at the moment the order was posted - an
 * average-cost estimate, NOT a GL-verified figure - so every consumer of
 * this service must render the "Estimated" badge and must never fold
 * estimated_margin into ProfitLossReportService's authoritative gross/net
 * profit. Extends BaseOrderReportService purely to reuse
 * applyCommonFilters()'s business/branch/date/product/customer scoping.
 */
class ProductMarginReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $sales = OrderDetail::query()
            ->join('orders', 'orders.order_id', '=', 'order_details.order_id')
            ->leftJoin('products', 'products.product_id', '=', 'order_details.product_id')
            ->where('orders.status', 'posted')
            ->where('orders.is_deleted', 0);

        $this->applyCommonFilters($sales, $obj, [
            'business'  => 'orders.business_id',
            'branch'    => 'orders.branch_id',
            'date'      => 'orders.sale_date',
            'customer'  => 'orders.user_id',
            'product'   => 'order_details.product_id',
            'variation' => 'order_details.product_variation_id',
        ]);

        if (!empty($obj['category_id'])) {
            $sales->where('products.category_id', $obj['category_id']);
        }
        if (!empty($obj['brand_id'])) {
            $sales->where('products.brand_id', $obj['brand_id']);
        }

        $sales_rows = $sales->select(
                'order_details.product_id',
                'products.name as product_name',
                'products.category_id',
                'products.brand_id'
            )
            ->selectRaw('SUM(order_details.total) as revenue')
            ->selectRaw('SUM(order_details.cost_price * order_details.quantity) as cogs')
            ->selectRaw('SUM(order_details.quantity) as qty_sold')
            ->groupBy('order_details.product_id', 'products.name', 'products.category_id', 'products.brand_id')
            ->get()
            ->keyBy('product_id');

        // Net out approved returns using the identical cost_price snapshot
        // convention (order_return_details.cost_price), so a returned line
        // never inflates both revenue and margin.
        $returns = OrderReturnDetail::query()
            ->join('order_returns', 'order_returns.order_return_id', '=', 'order_return_details.order_return_id')
            ->where('order_returns.is_deleted', 0)
            ->where('order_returns.status', 'approved');

        $this->applyCommonFilters($returns, $obj, [
            'business' => 'order_returns.business_id',
            'branch'   => 'order_returns.branch_id',
            'date'     => 'order_returns.order_return_date',
            'product'  => 'order_return_details.product_id',
        ]);

        $return_rows = $returns->select('order_return_details.product_id')
            ->selectRaw('SUM(order_return_details.total) as returned_revenue')
            ->selectRaw('SUM(order_return_details.cost_price * order_return_details.return_quantity) as returned_cogs')
            ->groupBy('order_return_details.product_id')
            ->get()
            ->keyBy('product_id');

        return $sales_rows->map(function ($row) use ($return_rows) {
            $ret = $return_rows[$row->product_id] ?? null;

            $net_revenue = (float) $row->revenue - (float) ($ret->returned_revenue ?? 0);
            $net_cogs = (float) $row->cogs - (float) ($ret->returned_cogs ?? 0);
            $margin = $net_revenue - $net_cogs;

            return (object) [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'category_id' => $row->category_id,
                'brand_id' => $row->brand_id,
                'qty_sold' => (float) $row->qty_sold,
                'net_revenue' => round($net_revenue, 2),
                'estimated_cogs' => round($net_cogs, 2),
                'estimated_margin' => round($margin, 2),
                'estimated_margin_pct' => $net_revenue > 0 ? round($margin / $net_revenue * 100, 1) : null,
                'is_estimated' => true,
            ];
        })->sortByDesc('estimated_margin')->values();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_revenue' => currency(round($rows->sum('net_revenue'), 2)),
            'grand_cogs' => currency(round($rows->sum('estimated_cogs'), 2)),
            'grand_margin' => currency(round($rows->sum('estimated_margin'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('product_name', fn ($row) => $row->product_name)
            ->editColumn('qty_sold', fn ($row) => decimal($row->qty_sold))
            ->editColumn('net_revenue', fn ($row) => currency($row->net_revenue))
            ->editColumn('estimated_cogs', fn ($row) => currency($row->estimated_cogs))
            ->editColumn('estimated_margin', fn ($row) => currency($row->estimated_margin))
            ->addColumn('estimated_margin_pct', fn ($row) => $row->estimated_margin_pct !== null ? $row->estimated_margin_pct . '%' : '-')
            ->with($totals)
            ->make(true);
    }
}
