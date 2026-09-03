<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\OrderDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Top-selling products/variations - order_details grouped by product or
 * variation (default variation, per the plan), ranked by quantity or net
 * sales (default net sales), limited to top N (default 50). Posted/
 * non-deleted orders only, same convention as the other sales reports.
 *
 * When grouping by variation, order_details.product_id is included in the
 * GROUP BY alongside product_variation_id so the joined products.name stays
 * functionally dependent under MySQL's ONLY_FULL_GROUP_BY.
 */
class TopSellingReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $query = OrderDetail::query()
            ->join('orders', 'orders.order_id', '=', 'order_details.order_id')
            ->leftJoin('products', 'products.product_id', '=', 'order_details.product_id')
            ->leftJoin('product_variations', 'product_variations.product_variation_id', '=', 'order_details.product_variation_id')
            ->where('orders.status', 'posted')
            ->where('orders.is_deleted', 0);

        $this->applyCommonFilters($query, $obj, [
            'business'     => 'orders.business_id',
            'branch'       => 'orders.branch_id',
            'date'         => 'orders.sale_date',
            'order_source' => 'orders.order_source_id',
            'customer'     => 'orders.user_id',
            'product'      => 'order_details.product_id',
            'variation'    => 'order_details.product_variation_id',
        ]);

        $group_by = ($obj['group_by'] ?? 'variation') === 'product' ? 'product' : 'variation';
        $rank_by = ($obj['rank_by'] ?? 'net') === 'quantity' ? 'total_qty' : 'net';
        $limit = (int) ($obj['limit'] ?? 50) ?: 50;

        $select = [
            'order_details.product_id',
            'products.name as product_name',
            DB::raw('SUM(order_details.quantity) as total_qty'),
            DB::raw('SUM(order_details.subtotal) as gross'),
            DB::raw('SUM(order_details.discount_amount) as discount'),
            DB::raw('SUM(order_details.tax_amount) as tax'),
            DB::raw('SUM(order_details.total) as net'),
        ];

        if ($group_by === 'product') {
            // products.name must be in GROUP BY for MySQL ONLY_FULL_GROUP_BY
            // (same pattern as ProductSalesReportService).
            $query->groupBy('order_details.product_id', 'products.name');
        } else {
            $select[] = 'order_details.product_variation_id';
            $select[] = 'product_variations.name as variation_name';
            $select[] = 'product_variations.sku';
            $query->groupBy([
                'order_details.product_id',
                'order_details.product_variation_id',
                'products.name',
                'product_variations.name',
                'product_variations.sku',
            ]);
        }

        $rows = $query->select($select)
            ->orderBy($rank_by, 'desc')
            ->limit($limit)
            ->get();

        return $rows->values()->map(function ($row, $i) {
            $row->rank = $i + 1;

            return $row;
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_qty' => decimal(round($rows->sum('total_qty'), 3)),
            'grand_net' => currency(round($rows->sum('net'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('rank', fn ($row) => $row->rank)
            ->addColumn('product_name', fn ($row) => $row->product_name)
            ->addColumn('variation_name', fn ($row) => $row->variation_name ?? '')
            ->addColumn('sku', fn ($row) => $row->sku ?? '')
            ->editColumn('total_qty', fn ($row) => decimal($row->total_qty))
            ->editColumn('net', fn ($row) => currency($row->net))
            ->rawColumns(['rank', 'product_name', 'variation_name', 'sku'])
            ->with($totals)
            ->make(true);
    }
}
