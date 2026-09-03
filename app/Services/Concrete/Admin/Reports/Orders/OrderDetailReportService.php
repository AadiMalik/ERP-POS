<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\OrderDetail;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Line-item audit report - every order_details row joined to its parent
 * order/product/variation/branch/order-source/customer, across ALL order
 * statuses (unlike the *-sales reports, which are posted-only aggregates).
 * Raw query-builder joins + explicit select, same style as
 * PurchaseReturnDetailReportService, to avoid N+1 and column ambiguity.
 * Delivery charge has no column/concept in this ERP, so it is reported as 0.
 */
class OrderDetailReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $query = OrderDetail::query()
            ->join('orders', 'orders.order_id', '=', 'order_details.order_id')
            ->leftJoin('products', 'products.product_id', '=', 'order_details.product_id')
            ->leftJoin('product_variations', 'product_variations.product_variation_id', '=', 'order_details.product_variation_id')
            ->leftJoin('branches', 'branches.branch_id', '=', 'orders.branch_id')
            ->leftJoin('order_sources', 'order_sources.order_source_id', '=', 'orders.order_source_id')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->where('orders.is_deleted', 0);

        $this->applyCommonFilters($query, $obj, [
            'business'     => 'orders.business_id',
            'branch'       => 'orders.branch_id',
            'date'         => 'orders.sale_date',
            'order_source' => 'orders.order_source_id',
            'status'       => 'orders.status',
            'customer'     => 'orders.user_id',
            'product'      => 'order_details.product_id',
            'variation'    => 'order_details.product_variation_id',
        ]);

        $rows = $query->orderBy('orders.order_date')
            ->get([
                'order_details.order_detail_id',
                'order_details.order_id',
                'order_details.product_id',
                'order_details.product_variation_id',
                'order_details.quantity',
                'order_details.unit_price',
                'order_details.discount_amount',
                'order_details.tax',
                'order_details.tax_amount',
                'order_details.subtotal',
                'order_details.total',
                'orders.daily_order_id',
                'orders.order_date',
                'orders.status as order_status',
                'orders.total as order_total',
                'orders.paid_amount as order_paid_amount',
                'branches.name as branch_name',
                'order_sources.name as order_source_name',
                'users.name as customer_name',
                'products.name as product_name',
                'product_variations.name as variation_name',
                'product_variations.sku',
            ]);

        return $this->filterByPaymentStatus($rows, $obj['payment_status'] ?? null, 'order_total', 'order_paid_amount');
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_qty'      => decimal(round($rows->sum('quantity'), 3)),
            'grand_gross'    => currency(round($rows->sum('subtotal'), 2)),
            'grand_discount' => currency(round($rows->sum('discount_amount'), 2)),
            'grand_tax'      => currency(round($rows->sum('tax_amount'), 2)),
            'grand_delivery' => currency(0),
            'grand_net'      => currency(round($rows->sum('total'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('order_no', fn ($row) => $row->daily_order_id)
            ->addColumn('order_date', fn ($row) => optional($row->order_date)->format('d-m-Y H:i'))
            ->addColumn('customer', fn ($row) => $row->customer_name ?? 'Walk-in')
            ->addColumn('branch', fn ($row) => $row->branch_name ?? '')
            ->addColumn('order_source', fn ($row) => $row->order_source_name ?? '')
            ->addColumn('order_status', fn ($row) => ucfirst(str_replace('_', ' ', $row->order_status)))
            ->addColumn('payment_status', fn ($row) => ucwords(str_replace('_', ' ', $this->paymentStatusOf($row->order_total, $row->order_paid_amount))))
            ->addColumn('product_name', fn ($row) => $row->product_name ?? '')
            ->addColumn('variation_name', fn ($row) => $row->variation_name ?? '')
            ->addColumn('sku', fn ($row) => $row->sku ?? '')
            ->editColumn('quantity', fn ($row) => decimal($row->quantity))
            ->editColumn('unit_price', fn ($row) => currency($row->unit_price))
            ->editColumn('discount_amount', fn ($row) => currency($row->discount_amount))
            ->editColumn('tax_amount', fn ($row) => currency($row->tax_amount))
            ->addColumn('delivery_charge', fn ($row) => currency(0))
            ->editColumn('total', fn ($row) => currency($row->total))
            ->addColumn('action', fn ($row) => "<a class='btn btn-icon btn-outline-secondary' target='_blank' title='View Order' href='" . route('order.print', $row->order_id) . "'><i class='fa fa-file-text'></i></a>")
            ->rawColumns(['order_no', 'order_date', 'customer', 'branch', 'order_source', 'order_status', 'payment_status', 'product_name', 'variation_name', 'sku', 'action'])
            ->with($totals)
            ->make(true);
    }
}
