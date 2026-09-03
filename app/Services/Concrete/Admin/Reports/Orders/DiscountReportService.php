<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\OrderDetail;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Line-level discount breakdown - every order_details row that actually
 * carried a discount, on posted/non-deleted orders (matches
 * SalesReportService's hardcoded status convention for this report family).
 * Discount "type"/"name" come from the orders.discount_id -> discounts rule
 * when one was attached; not every discounted line has a rule (manual line
 * discounts), so both fall back to 'N/A'.
 */
class DiscountReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $query = OrderDetail::query()
            ->join('orders', 'orders.order_id', '=', 'order_details.order_id')
            ->leftJoin('products', 'products.product_id', '=', 'order_details.product_id')
            ->leftJoin('product_variations', 'product_variations.product_variation_id', '=', 'order_details.product_variation_id')
            ->leftJoin('discounts', 'discounts.discount_id', '=', 'orders.discount_id')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->where('order_details.discount_amount', '>', 0)
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

        return $query->orderBy('orders.order_date')
            ->get([
                'orders.daily_order_id',
                'orders.order_id',
                'orders.order_date',
                'users.name as customer_name',
                'products.name as product_name',
                'product_variations.name as variation_name',
                'product_variations.sku',
                'discounts.type as discount_type',
                'discounts.name as discount_name',
                'order_details.discount_amount',
                'order_details.total as net_amount',
            ]);
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_discount' => currency(round($rows->sum('discount_amount'), 2)),
            'grand_net'      => currency(round($rows->sum('net_amount'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('order_no', fn ($row) => $row->daily_order_id)
            ->addColumn('order_date', fn ($row) => optional($row->order_date)->format('d-m-Y H:i'))
            ->addColumn('customer_name', fn ($row) => $row->customer_name ?? 'Walk-in')
            ->addColumn('discount_type', fn ($row) => $row->discount_type ? ucfirst($row->discount_type) : 'N/A')
            ->editColumn('discount_amount', fn ($row) => currency($row->discount_amount))
            ->editColumn('net_amount', fn ($row) => currency($row->net_amount))
            ->addColumn('action', fn ($row) => "<a class='btn btn-icon btn-outline-secondary' target='_blank' title='View Order' href='" . route('order.print', $row->order_id) . "'><i class='fa fa-file-text'></i></a>")
            ->rawColumns(['order_no', 'order_date', 'customer_name', 'action'])
            ->with($totals)
            ->make(true);
    }
}
