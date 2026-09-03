<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\Order;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Orders with an outstanding due balance - due is computed, never stored
 * (see BaseOrderReportService::dueOf()), so the >0 filter runs on the
 * collection after the common WHERE filters, same pattern OrderService::
 * history() uses for its own payment-status filtering.
 */
class DueCreditSalesReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $query = Order::query()->where('is_deleted', 0);

        $this->applyCommonFilters($query, $obj);

        $rows = $query->with(['branch', 'user'])
            ->orderBy('order_date', 'desc')
            ->get();

        return $rows->filter(fn ($row) => $this->dueOf($row->total, $row->paid_amount) > 0)->values();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_orders'    => $rows->count(),
            'grand_total'     => currency(round($rows->sum('total'), 2)),
            'grand_paid'      => currency(round($rows->sum('paid_amount'), 2)),
            'grand_due'       => currency(round($rows->sum(fn ($row) => $this->dueOf($row->total, $row->paid_amount)), 2)),
            'grand_customers' => $rows->pluck('user_id')->filter()->unique()->count(),
        ];

        return DataTables::of($rows)
            ->addColumn('order_no', fn ($row) => $row->daily_order_id)
            ->addColumn('order_date', fn ($row) => optional($row->order_date)->format('d-m-Y H:i'))
            ->addColumn('customer', fn ($row) => $row->user->name ?? 'Walk-in')
            ->addColumn('branch', fn ($row) => $row->branch->name ?? '')
            ->editColumn('total', fn ($row) => currency($row->total))
            ->editColumn('paid_amount', fn ($row) => currency($row->paid_amount))
            ->addColumn('due_amount', fn ($row) => currency($this->dueOf($row->total, $row->paid_amount)))
            ->addColumn('payment_status', fn ($row) => ucwords(str_replace('_', ' ', $this->paymentStatusOf($row->total, $row->paid_amount))))
            ->addColumn('action', fn ($row) => '<a href="' . route('order.print', $row->order_id) . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-eye"></i> View</a>')
            ->rawColumns(['order_no', 'order_date', 'customer', 'branch', 'payment_status', 'action'])
            ->with($totals)
            ->make(true);
    }
}
