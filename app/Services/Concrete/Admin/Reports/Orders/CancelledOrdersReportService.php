<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\Order;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Orders in status cancelled/void, with the cancellation reason and who
 * cancelled read off the latest matching OrderStatusHistory row per order
 * (status is fixed to cancelled+void by design, so no status filter/dropdown
 * here - see BaseOrderReportService::applyCommonFilters() for the rest of
 * the shared filter set).
 */
class CancelledOrdersReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $query = Order::query()->where('is_deleted', 0)->whereIn('status', ['cancelled', 'void']);

        $this->applyCommonFilters($query, $obj);

        return $query->with(['branch', 'orderSource', 'user', 'statusHistory.changedby'])
            ->orderBy('order_date', 'desc')
            ->get();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_orders' => $rows->count(),
            'grand_amount' => currency(round($rows->sum('total'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('order_no', fn ($row) => $row->daily_order_id)
            ->addColumn('order_date', fn ($row) => optional($row->order_date)->format('d-m-Y H:i'))
            ->addColumn('customer', fn ($row) => $row->user->name ?? 'Walk-in')
            ->addColumn('branch', fn ($row) => $row->branch->name ?? '')
            ->addColumn('order_source', fn ($row) => $row->orderSource->name ?? '')
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->editColumn('total', fn ($row) => currency($row->total))
            ->addColumn('cancellation_reason', fn ($row) => optional($this->cancellationHistory($row))->reason ?? '-')
            ->addColumn('cancelled_by', fn ($row) => optional(optional($this->cancellationHistory($row))->changedby)->name ?? '-')
            ->addColumn('action', fn ($row) => '<a href="' . route('order.print', $row->order_id) . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-eye"></i> View</a>')
            ->rawColumns(['order_no', 'order_date', 'customer', 'branch', 'order_source', 'status', 'cancellation_reason', 'cancelled_by', 'action'])
            ->with($totals)
            ->make(true);
    }

    protected function cancellationHistory(Order $row)
    {
        return $row->statusHistory
            ->whereIn('to_status', ['cancelled', 'void'])
            ->sortByDesc('date_created')
            ->first();
    }
}
