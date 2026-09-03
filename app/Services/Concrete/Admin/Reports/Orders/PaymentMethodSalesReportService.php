<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Payment-wise sales - different grain from the other 3 sibling reports: no
 * fan-out risk here, since order_payments -> orders is a safe many-to-one
 * join (unlike order_details -> orders, which needs the two-query merge
 * pattern). Reads OrderPayment grouped by payment_method_id, scoped to
 * posted/non-deleted orders in the filtered range. Also exposes a
 * multi-payment-order count (orders paid via more than one distinct payment
 * method) as a summary stat, not a table row.
 */
class PaymentMethodSalesReportService extends BaseOrderReportService
{
    protected function columnOverrides(): array
    {
        return [
            'business'     => 'orders.business_id',
            'branch'       => 'orders.branch_id',
            'date'         => 'orders.sale_date',
            'order_source' => 'orders.order_source_id',
            'status'       => 'orders.status',
            'customer'     => 'orders.user_id',
        ];
    }

    protected function scopedPaymentsQuery(array $obj)
    {
        $query = OrderPayment::query()
            ->where('order_payments.is_deleted', 0)
            ->join('orders', 'orders.order_id', '=', 'order_payments.order_id')
            ->where('orders.status', 'posted')
            ->where('orders.is_deleted', 0);

        $this->applyCommonFilters($query, $obj, $this->columnOverrides());

        return $query;
    }

    public function build(array $obj): Collection
    {
        $rows = $this->scopedPaymentsQuery($obj)
            ->select(
                'order_payments.payment_method_id',
                DB::raw('COUNT(DISTINCT order_payments.order_id) as order_count'),
                DB::raw('SUM(order_payments.amount) as total_amount')
            )
            ->groupBy('order_payments.payment_method_id')
            ->get();

        $method_ids = $rows->pluck('payment_method_id')->filter()->all();
        $methods = PaymentMethod::whereIn('payment_method_id', $method_ids)->pluck('name', 'payment_method_id');

        return $rows->map(fn ($row) => (object) [
            'payment_method_id' => $row->payment_method_id,
            'payment_method'    => $methods[$row->payment_method_id] ?? '',
            'order_count'       => (int) $row->order_count,
            'total_amount'      => (float) ($row->total_amount ?? 0),
        ])->values();
    }

    public function multiPaymentOrderCount(array $obj): int
    {
        return $this->scopedPaymentsQuery($obj)
            ->groupBy('order_payments.order_id')
            ->havingRaw('COUNT(DISTINCT order_payments.payment_method_id) > 1')
            ->get(['order_payments.order_id'])
            ->count();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_orders'         => $rows->sum('order_count'),
            'grand_total'          => currency(round($rows->sum('total_amount'), 2)),
            'multi_payment_orders' => $this->multiPaymentOrderCount($obj),
        ];

        return DataTables::of($rows)
            ->addColumn('payment_method', fn ($row) => $row->payment_method)
            ->editColumn('order_count', fn ($row) => $row->order_count)
            ->editColumn('total_amount', fn ($row) => currency($row->total_amount))
            ->rawColumns(['payment_method'])
            ->with($totals)
            ->make(true);
    }
}
