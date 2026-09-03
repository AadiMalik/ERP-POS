<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\Order;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Order-level loyalty activity - every order where loyalty points were
 * either redeemed or earned. loyalty_points_used/loyalty_discount_amount/
 * loyalty_points_earned are frozen on the order at posting time
 * (LoyaltyPointService), so this report only queries/displays them - never
 * recomputes. Mirrors DiscountReportService's shape but rooted on Order
 * (not OrderDetail), since loyalty is applied at the order level, not per line.
 */
class LoyaltyReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $query = Order::query()
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->where('orders.is_deleted', 0)
            ->where(function ($q) {
                $q->where('orders.loyalty_points_used', '>', 0)
                    ->orWhere('orders.loyalty_points_earned', '>', 0);
            });

        $this->applyCommonFilters($query, $obj, [
            'business' => 'orders.business_id',
            'branch'   => 'orders.branch_id',
            'date'     => 'orders.sale_date',
            'status'   => 'orders.status',
            'customer' => 'orders.user_id',
        ]);

        if (!empty($obj['order_no'])) {
            $query->where('orders.daily_order_id', 'like', '%' . $obj['order_no'] . '%');
        }

        return $query->orderBy('orders.order_date')
            ->get([
                'orders.order_id',
                'orders.daily_order_id',
                'orders.order_date',
                'orders.total',
                'orders.status',
                'orders.loyalty_points_used',
                'orders.loyalty_discount_amount',
                'orders.loyalty_points_earned',
                'users.name as customer_name',
            ]);
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_points_used'   => decimal(round($rows->sum('loyalty_points_used'), 3)),
            'grand_discount'      => currency(round($rows->sum('loyalty_discount_amount'), 2)),
            'grand_points_earned' => decimal(round($rows->sum('loyalty_points_earned'), 3)),
        ];

        return DataTables::of($rows)
            ->addColumn('order_no', fn ($row) => $row->daily_order_id)
            ->addColumn('order_date', fn ($row) => optional($row->order_date)->format('d-m-Y H:i'))
            ->addColumn('customer_name', fn ($row) => $row->customer_name ?? 'Walk-in')
            ->editColumn('total', fn ($row) => currency($row->total))
            ->editColumn('loyalty_points_used', fn ($row) => decimal($row->loyalty_points_used))
            ->editColumn('loyalty_discount_amount', fn ($row) => currency($row->loyalty_discount_amount))
            ->editColumn('loyalty_points_earned', fn ($row) => decimal($row->loyalty_points_earned))
            ->addColumn('status', fn ($row) => ucfirst(str_replace('_', ' ', $row->status)))
            ->addColumn('action', fn ($row) => "<a class='btn btn-icon btn-outline-secondary' target='_blank' title='View Order' href='" . route('order.print', $row->order_id) . "'><i class='fa fa-file-text'></i></a>")
            ->rawColumns(['order_no', 'order_date', 'customer_name', 'status', 'action'])
            ->with($totals)
            ->make(true);
    }
}
