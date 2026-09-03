<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Order Source-wise sales - same two-query pattern as CustomerSalesReportService
 * / BranchSalesReportService (order-level totals grouped by order_source_id,
 * merged with a separate order_details-joined-to-orders quantity aggregate).
 * Order source labels are always read from the per-business `order_sources`
 * lookup table (OrderSource model), never hardcoded. Only "posted" orders
 * count as sales. Order source itself is the grouping dimension, so no order
 * source filter dropdown is offered.
 */
class OrderSourceSalesReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $order_query = Order::query()->where('is_deleted', 0)->where('status', 'posted');
        $this->applyCommonFilters($order_query, $obj);

        $order_rows = $order_query->select(
            'order_source_id',
            DB::raw('COUNT(*) as order_count'),
            DB::raw('SUM(subtotal) as gross'),
            DB::raw('SUM(total) as net')
        )->groupBy('order_source_id')->get()->keyBy('order_source_id');

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

        $qty_rows = $qty_query->select('orders.order_source_id', DB::raw('SUM(order_details.quantity) as total_qty'))
            ->groupBy('orders.order_source_id')->get()->keyBy('order_source_id');

        $source_ids = $order_rows->keys()->filter()->all();
        $sources = OrderSource::whereIn('order_source_id', $source_ids)->pluck('name', 'order_source_id');

        return $order_rows->map(function ($row, $order_source_id) use ($qty_rows, $sources) {
            return (object) [
                'order_source_id' => $order_source_id,
                'order_source'    => $sources[$order_source_id] ?? '',
                'order_count'     => (int) $row->order_count,
                'total_qty'       => (float) ($qty_rows[$order_source_id]->total_qty ?? 0),
                'gross'           => (float) ($row->gross ?? 0),
                'net'             => (float) ($row->net ?? 0),
            ];
        })->values();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_orders' => $rows->sum('order_count'),
            'grand_qty'    => round($rows->sum('total_qty'), 2),
            'grand_gross'  => currency(round($rows->sum('gross'), 2)),
            'grand_net'    => currency(round($rows->sum('net'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('order_source', fn ($row) => $row->order_source)
            ->editColumn('order_count', fn ($row) => $row->order_count)
            ->editColumn('total_qty', fn ($row) => round($row->total_qty, 2))
            ->editColumn('gross', fn ($row) => currency($row->gross))
            ->editColumn('net', fn ($row) => currency($row->net))
            ->rawColumns(['order_source'])
            ->with($totals)
            ->make(true);
    }
}
