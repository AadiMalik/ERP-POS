<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Customer-wise sales - order-level totals (Order, grouped by user_id) merged
 * with a separate line-item quantity aggregate (OrderDetail joined to
 * orders), per the two-query pattern mandated for every report that needs
 * both an order-level total AND a product-quantity total in the same grouped
 * row (joining orders -> order_details and summing order-level columns in
 * that same grouped query would multiply subtotal/total by line-item count -
 * see BaseOrderReportService/OrderSummaryReportService siblings). Only
 * "posted" orders count as sales, matching SalesReportService's convention -
 * no status filter is exposed to the user.
 */
class CustomerSalesReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $order_query = Order::query()->where('is_deleted', 0)->where('status', 'posted');
        $this->applyCommonFilters($order_query, $obj);

        $order_rows = $order_query->select(
            'user_id',
            DB::raw('COUNT(*) as order_count'),
            DB::raw('SUM(subtotal) as gross'),
            DB::raw('SUM(discount_amount) as discount'),
            DB::raw('SUM(tax_amount) as tax'),
            DB::raw('SUM(total) as net'),
            DB::raw('SUM(paid_amount) as paid')
        )->groupBy('user_id')->get()->keyBy('user_id');

        $qty_query = OrderDetail::query()
            ->join('orders', 'orders.order_id', '=', 'order_details.order_id')
            ->where('orders.is_deleted', 0)
            ->where('orders.status', 'posted');
        $this->applyCommonFilters($qty_query, $obj, [
            'business'     => 'orders.business_id',
            'branch'       => 'orders.branch_id',
            'date'         => 'orders.sale_date',
            'order_source' => 'orders.order_source_id',
            'status'       => 'orders.status',
            'customer'     => 'orders.user_id',
        ]);

        $qty_rows = $qty_query->select('orders.user_id', DB::raw('SUM(order_details.quantity) as total_qty'))
            ->groupBy('orders.user_id')->get()->keyBy('user_id');

        $user_ids = $order_rows->keys()->filter()->all();
        $users = User::whereIn('id', $user_ids)->pluck('name', 'id');

        return $order_rows->map(function ($row, $user_id) use ($qty_rows, $users) {
            $net = (float) ($row->net ?? 0);
            $paid = (float) ($row->paid ?? 0);

            return (object) [
                'user_id'      => $user_id,
                'customer'     => $user_id ? ($users[$user_id] ?? '') : 'Walk-in',
                'order_count'  => (int) $row->order_count,
                'total_qty'    => (float) ($qty_rows[$user_id]->total_qty ?? 0),
                'gross'        => (float) ($row->gross ?? 0),
                'discount'     => (float) ($row->discount ?? 0),
                'net'          => $net,
                'paid_amount'  => $paid,
                'due_amount'   => $this->dueOf($net, $paid),
            ];
        })->values();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_orders'   => $rows->sum('order_count'),
            'grand_qty'      => round($rows->sum('total_qty'), 2),
            'grand_gross'    => currency(round($rows->sum('gross'), 2)),
            'grand_discount' => currency(round($rows->sum('discount'), 2)),
            'grand_net'      => currency(round($rows->sum('net'), 2)),
            'grand_paid'     => currency(round($rows->sum('paid_amount'), 2)),
            'grand_due'      => currency(round($rows->sum('due_amount'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('customer', fn ($row) => $row->customer)
            ->editColumn('order_count', fn ($row) => $row->order_count)
            ->editColumn('total_qty', fn ($row) => round($row->total_qty, 2))
            ->editColumn('gross', fn ($row) => currency($row->gross))
            ->editColumn('discount', fn ($row) => currency($row->discount))
            ->editColumn('net', fn ($row) => currency($row->net))
            ->editColumn('paid_amount', fn ($row) => currency($row->paid_amount))
            ->editColumn('due_amount', fn ($row) => currency($row->due_amount))
            ->rawColumns(['customer'])
            ->with($totals)
            ->make(true);
    }
}
