<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Enums\ComplimentaryStatus;
use App\Models\OrderDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Line-level complimentary giveaway report - every posted order_details row
 * marked complimentary. Retail value is the original selling amount given
 * free; actual cost is the inventory cost snapshot taken at posting.
 */
class ComplimentaryReportService extends BaseOrderReportService
{
    public function build(array $obj): Collection
    {
        $query = OrderDetail::query()
            ->join('orders', 'orders.order_id', '=', 'order_details.order_id')
            ->leftJoin('products', 'products.product_id', '=', 'order_details.product_id')
            ->leftJoin('product_variations', 'product_variations.product_variation_id', '=', 'order_details.product_variation_id')
            ->leftJoin('users as customers', 'customers.id', '=', 'orders.user_id')
            ->leftJoin('users as issued_by', 'issued_by.id', '=', 'order_details.complimentary_by_id')
            ->leftJoin('complimentary_reasons as line_reasons', 'line_reasons.complimentary_reason_id', '=', 'order_details.complimentary_reason_id')
            ->leftJoin('complimentary_reasons as order_reasons', 'order_reasons.complimentary_reason_id', '=', 'orders.complimentary_reason_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'orders.warehouse_id')
            ->where('order_details.is_complimentary', 1)
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

        if (!empty($obj['warehouse_id'])) {
            $query->where('orders.warehouse_id', $obj['warehouse_id']);
        }
        if (!empty($obj['complimentary_status']) && $obj['complimentary_status'] !== '0') {
            $query->where('orders.complimentary_status', $obj['complimentary_status']);
        }
        if (!empty($obj['complimentary_reason_id'])) {
            $query->where(function ($q) use ($obj) {
                $q->where('order_details.complimentary_reason_id', $obj['complimentary_reason_id'])
                    ->orWhere('orders.complimentary_reason_id', $obj['complimentary_reason_id']);
            });
        }
        if (!empty($obj['issued_by_id'])) {
            $query->where(function ($q) use ($obj) {
                $q->where('order_details.complimentary_by_id', $obj['issued_by_id'])
                    ->orWhere('orders.complimentary_by_id', $obj['issued_by_id']);
            });
        }
        if (!empty($obj['daily_order_id'])) {
            $query->where('orders.daily_order_id', 'like', '%' . $obj['daily_order_id'] . '%');
        }

        return $query->orderBy('orders.order_date')
            ->get([
                'orders.daily_order_id',
                'orders.order_id',
                'orders.order_date',
                'orders.complimentary_status',
                'customers.name as customer_name',
                'products.name as product_name',
                'product_variations.name as variation_name',
                'order_details.quantity',
                'order_details.unit_price',
                'order_details.complimentary_value',
                'order_details.cost_price',
                'order_details.base_quantity',
                'line_reasons.name as line_reason_name',
                'order_reasons.name as order_reason_name',
                'warehouses.name as warehouse_name',
                'issued_by.name as issued_by_name',
            ]);
    }

    public function summarize(Collection $rows): array
    {
        $can_view_cost = Auth::user()->can('order.complimentary.view-cost');
        $order_ids = $rows->pluck('order_id')->unique();

        $reason_breakdown = $rows->groupBy(function ($row) {
            return $row->line_reason_name ?: ($row->order_reason_name ?: 'N/A');
        })->map(function ($group) {
            return [
                'reason' => $group->first()->line_reason_name ?: ($group->first()->order_reason_name ?: 'N/A'),
                'qty' => round($group->sum('quantity'), 3),
                'retail' => round($group->sum('complimentary_value'), 2),
                'cost' => round($group->sum(function ($row) {
                    return (float) $row->cost_price * (float) $row->base_quantity;
                }), 2),
            ];
        })->values();

        return [
            'total_orders' => $order_ids->count(),
            'total_qty' => round($rows->sum('quantity'), 3),
            'total_retail' => round($rows->sum('complimentary_value'), 2),
            'total_cost' => $can_view_cost
                ? round($rows->sum(function ($row) {
                    return (float) $row->cost_price * (float) $row->base_quantity;
                }), 2)
                : null,
            'reason_breakdown' => $reason_breakdown,
            'can_view_cost' => $can_view_cost,
        ];
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);
        $summary = $this->summarize($rows);
        $can_view_cost = $summary['can_view_cost'];

        return DataTables::of($rows)
            ->addColumn('order_no', fn ($row) => $row->daily_order_id)
            ->addColumn('order_date', fn ($row) => localDateTime($row->order_date))
            ->addColumn('customer_name', fn ($row) => $row->customer_name ?? 'Walk-in')
            ->addColumn('complimentary_status_label', fn ($row) => ComplimentaryStatus::getOptions()[$row->complimentary_status] ?? $row->complimentary_status)
            ->addColumn('reason_name', fn ($row) => $row->line_reason_name ?: ($row->order_reason_name ?: '-'))
            ->editColumn('unit_price', fn ($row) => currency($row->unit_price))
            ->addColumn('retail_value', fn ($row) => currency($row->complimentary_value))
            ->addColumn('actual_cost', function ($row) use ($can_view_cost) {
                if (!$can_view_cost) {
                    return '-';
                }
                return currency((float) $row->cost_price * (float) $row->base_quantity);
            })
            ->addColumn('action', fn ($row) => "<a class='btn btn-icon btn-outline-secondary' target='_blank' title='View Order' href='" . route('order.print', $row->order_id) . "'><i class='fa fa-file-text'></i></a>")
            ->rawColumns(['action'])
            ->with([
                'kpi_orders' => $summary['total_orders'],
                'kpi_qty' => $summary['total_qty'],
                'kpi_retail' => currency($summary['total_retail']),
                'kpi_cost' => $can_view_cost ? currency($summary['total_cost']) : '-',
                'reason_breakdown' => $summary['reason_breakdown'],
            ])
            ->make(true);
    }
}
