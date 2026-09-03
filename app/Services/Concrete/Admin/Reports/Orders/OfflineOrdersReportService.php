<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\Order;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Orders placed via the offline desktop POS client (orders.pos_device_id
 * is set) so admins can see which sales came from which device and whether
 * it has synced. Same common filter set (business/branch/order_source/
 * status/date/customer) as every other Orders Report.
 */
class OfflineOrdersReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $query = Order::query()->where('is_deleted', 0)->whereNotNull('pos_device_id');

        $this->applyCommonFilters($query, $obj);

        return $query->with(['posDevice', 'branch', 'user'])
            ->orderBy('order_date', 'desc')
            ->get();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_orders' => $rows->count(),
            'grand_total'  => currency(round($rows->sum('total'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('order_no', fn ($row) => $row->daily_order_id)
            ->addColumn('order_date', fn ($row) => optional($row->order_date)->format('d-m-Y H:i'))
            ->addColumn('branch', fn ($row) => $row->branch->name ?? '')
            ->addColumn('device_name', fn ($row) => $row->posDevice->name ?? '-')
            ->addColumn('customer', fn ($row) => $row->user->name ?? 'Walk-in')
            ->addColumn('status', fn ($row) => ucfirst(str_replace('_', ' ', $row->status)))
            ->editColumn('total', fn ($row) => currency($row->total))
            ->addColumn('offline_local_id', fn ($row) => $row->offline_local_id ?? '-')
            ->addColumn('last_sync_at', fn ($row) => optional($row->posDevice)->last_sync_at ? $row->posDevice->last_sync_at->format('d-m-Y H:i') : '-')
            ->addColumn('action', fn ($row) => '<a href="' . route('order.print', $row->order_id) . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-eye"></i> View</a>')
            ->rawColumns(['order_no', 'order_date', 'branch', 'device_name', 'customer', 'status', 'action'])
            ->with($totals)
            ->make(true);
    }
}
