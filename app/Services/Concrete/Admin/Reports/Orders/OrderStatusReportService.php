<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Orders grouped by status - a genuinely aggregated query, so
 * applyCommonFilters() (plain WHERE clauses) runs before the
 * select()/groupBy(), per BaseOrderReportService's doc-comment. Due is
 * computed per group row (net - paid), same math as every other Orders
 * Report (BaseOrderReportService::dueOf()).
 */
class OrderStatusReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $query = Order::query()->where('is_deleted', 0);

        $this->applyCommonFilters($query, $obj);

        return $query->select(
            'status',
            DB::raw('COUNT(*) as order_count'),
            DB::raw('SUM(subtotal) as gross'),
            DB::raw('SUM(discount_amount) as discount'),
            DB::raw('SUM(tax_amount) as tax'),
            DB::raw('SUM(total) as net'),
            DB::raw('SUM(paid_amount) as paid')
        )
            ->groupBy('status')
            ->get();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_orders'   => $rows->sum('order_count'),
            'grand_gross'    => currency(round($rows->sum('gross'), 2)),
            'grand_discount' => currency(round($rows->sum('discount'), 2)),
            'grand_tax'      => currency(round($rows->sum('tax'), 2)),
            'grand_net'      => currency(round($rows->sum('net'), 2)),
            'grand_paid'     => currency(round($rows->sum('paid'), 2)),
            'grand_due'      => currency(round($rows->sum(fn ($row) => $this->dueOf($row->net, $row->paid)), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('status', fn ($row) => ucfirst(str_replace('_', ' ', $row->status)))
            ->editColumn('gross', fn ($row) => currency($row->gross))
            ->editColumn('discount', fn ($row) => currency($row->discount))
            ->editColumn('tax', fn ($row) => currency($row->tax))
            ->editColumn('net', fn ($row) => currency($row->net))
            ->editColumn('paid', fn ($row) => currency($row->paid))
            ->addColumn('due', fn ($row) => currency($this->dueOf($row->net, $row->paid)))
            ->rawColumns(['status'])
            ->with($totals)
            ->make(true);
    }
}
