<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\OrderDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Variation-wise sales aggregate - posted orders only (hardcoded, same
 * convention as SalesReportService). Grouped by product_variation_id, but
 * also joins products so the parent product name is shown alongside the
 * variation (management needs to identify exactly which variation was sold).
 */
class VariationSalesReportService extends BaseOrderReportService
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

        return $query->groupBy('order_details.product_variation_id', 'products.name', 'product_variations.name', 'product_variations.sku')
            ->get([
                'order_details.product_variation_id',
                'products.name as product_name',
                'product_variations.name as variation_name',
                'product_variations.sku',
                DB::raw('SUM(order_details.quantity) as total_qty'),
                DB::raw('SUM(order_details.subtotal) as gross'),
                DB::raw('SUM(order_details.discount_amount) as discount'),
                DB::raw('SUM(order_details.tax_amount) as tax'),
                DB::raw('SUM(order_details.total) as net'),
            ]);
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_qty'      => decimal(round($rows->sum('total_qty'), 3)),
            'grand_gross'    => currency(round($rows->sum('gross'), 2)),
            'grand_discount' => currency(round($rows->sum('discount'), 2)),
            'grand_tax'      => currency(round($rows->sum('tax'), 2)),
            'grand_net'      => currency(round($rows->sum('net'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('product_name', fn ($row) => $row->product_name ?? '')
            ->addColumn('variation_name', fn ($row) => $row->variation_name ?? '')
            ->addColumn('sku', fn ($row) => $row->sku ?? '')
            ->editColumn('total_qty', fn ($row) => decimal($row->total_qty))
            ->editColumn('gross', fn ($row) => currency($row->gross))
            ->editColumn('discount', fn ($row) => currency($row->discount))
            ->editColumn('tax', fn ($row) => currency($row->tax))
            ->editColumn('net', fn ($row) => currency($row->net))
            ->rawColumns(['product_name', 'variation_name', 'sku'])
            ->with($totals)
            ->make(true);
    }
}
