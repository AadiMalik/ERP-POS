<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Models\OrderDetail;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Line-level tax breakdown - every order_details row that actually carried
 * tax, on posted/non-deleted orders. Distinct from the accounting-side
 * TaxReportService (GL-account based) - this one is order/line based, own
 * permission slug reports.order-tax-report.*.
 */
class OrderTaxReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $query = OrderDetail::query()
            ->join('orders', 'orders.order_id', '=', 'order_details.order_id')
            ->leftJoin('products', 'products.product_id', '=', 'order_details.product_id')
            ->leftJoin('product_variations', 'product_variations.product_variation_id', '=', 'order_details.product_variation_id')
            ->where('order_details.tax_amount', '>', 0)
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
                'products.name as product_name',
                'product_variations.name as variation_name',
                'product_variations.sku',
                'order_details.tax as tax_rate',
                'order_details.subtotal as taxable_amount',
                'order_details.tax_amount',
                'order_details.total',
            ]);
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_taxable' => currency(round($rows->sum('taxable_amount'), 2)),
            'grand_tax'     => currency(round($rows->sum('tax_amount'), 2)),
            'grand_total'   => currency(round($rows->sum('total'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('order_no', fn ($row) => $row->daily_order_id)
            ->addColumn('order_date', fn ($row) => optional($row->order_date)->format('d-m-Y H:i'))
            ->editColumn('tax_rate', fn ($row) => decimal($row->tax_rate) . '%')
            ->editColumn('taxable_amount', fn ($row) => currency($row->taxable_amount))
            ->editColumn('tax_amount', fn ($row) => currency($row->tax_amount))
            ->editColumn('total', fn ($row) => currency($row->total))
            ->rawColumns(['order_no', 'order_date'])
            ->with($totals)
            ->make(true);
    }
}
