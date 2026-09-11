<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Enums\JournalSourceTypes;
use App\Models\CostPriceAdjustment;
use App\Models\JournalEntry;
use App\Services\Concrete\Admin\Reports\Inventory\Concerns\AppliesInventoryReportScope;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Cost Price Adjustment Report - every posted/pending/cancelled valuation
 * correction, with drill-down to the adjustment record and its Journal
 * Voucher. Reconciles with Stock Ledger/Stock Valuation and the GL since it
 * reads the same cost_price_adjustments rows those reports' totals derive
 * from once posted.
 */
class CostPriceAdjustmentReportService
{
    use AppliesInventoryReportScope;

    public function build(array $obj): Collection
    {
        $filters = $this->baseFilters($obj);

        $q = CostPriceAdjustment::query()
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'cost_price_adjustments.warehouse_id')
            ->leftJoin('branches', 'branches.branch_id', '=', 'warehouses.branch_id')
            ->join('products', 'products.product_id', '=', 'cost_price_adjustments.product_id')
            ->join('product_variations', 'product_variations.product_variation_id', '=', 'cost_price_adjustments.product_variation_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'cost_price_adjustments.createdby_id')
            ->leftJoin('users as approver', 'approver.id', '=', 'cost_price_adjustments.approvedby_id')
            ->where('cost_price_adjustments.is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $q->where('cost_price_adjustments.business_id', $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $q->where('warehouses.branch_id', $filters['branch_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $q->where('cost_price_adjustments.warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['product_id'])) {
            $q->where('cost_price_adjustments.product_id', $filters['product_id']);
        }
        if (!empty($filters['product_variation_id'])) {
            $q->where('cost_price_adjustments.product_variation_id', $filters['product_variation_id']);
        }
        if (!empty($obj['createdby_id'])) {
            $q->where('cost_price_adjustments.createdby_id', $obj['createdby_id']);
        }
        if (!empty($filters['status'])) {
            $q->where('cost_price_adjustments.status', $filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $q->whereDate('cost_price_adjustments.adjustment_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $q->whereDate('cost_price_adjustments.adjustment_date', '<=', $filters['end_date']);
        }

        $q = applyRoleScope($q, $filters['allow_roles'], 'cost_price_adjustments.business_id', 'warehouses.branch_id');

        $rows = $q->orderByDesc('cost_price_adjustments.adjustment_date')->get([
            'cost_price_adjustments.cost_price_adjustment_id',
            'cost_price_adjustments.business_id',
            'cost_price_adjustments.reference_no',
            'cost_price_adjustments.adjustment_date',
            'cost_price_adjustments.previous_cost_price',
            'cost_price_adjustments.new_cost_price',
            'cost_price_adjustments.quantity_on_hand',
            'cost_price_adjustments.difference_per_unit',
            'cost_price_adjustments.total_adjustment_amount',
            'cost_price_adjustments.reason',
            'cost_price_adjustments.status',
            'products.name as product_name',
            'product_variations.name as variation_name',
            'warehouses.name as warehouse_name',
            'branches.name as branch_name',
            'creator.name as created_by_name',
            'approver.name as approved_by_name',
        ]);

        // Resolve every posted JV in one query rather than one per row.
        $journal_entries = JournalEntry::where('source_type', JournalSourceTypes::INVENTORY_COST_ADJUSTMENT)
            ->whereIn('source_id', $rows->pluck('cost_price_adjustment_id'))
            ->where('is_deleted', 0)
            ->get(['journal_entry_id', 'entry_no', 'source_id'])
            ->keyBy('source_id');

        return $rows->map(function ($row) use ($journal_entries) {
            $journal_entry = $journal_entries->get($row->cost_price_adjustment_id);

            return (object) [
                'cost_price_adjustment_id' => $row->cost_price_adjustment_id,
                'reference_no'             => $row->reference_no,
                'adjustment_date'          => $row->adjustment_date,
                'product_name'             => $row->product_name,
                'variation_name'           => $row->variation_name,
                'warehouse_name'           => $row->warehouse_name,
                'branch_name'              => $row->branch_name,
                'previous_cost_price'      => (float) $row->previous_cost_price,
                'new_cost_price'           => (float) $row->new_cost_price,
                'quantity_on_hand'         => (float) $row->quantity_on_hand,
                'difference_per_unit'      => (float) $row->difference_per_unit,
                'total_adjustment_amount'  => (float) $row->total_adjustment_amount,
                'reason'                   => $row->reason,
                'status'                   => $row->status,
                'created_by_name'          => $row->created_by_name,
                'approved_by_name'         => $row->approved_by_name,
                'journal_entry_no'         => $journal_entry->entry_no ?? null,
                'detail_url'               => $row->status === 'pending'
                    ? url('/admin/cost-price-adjustment/' . $row->cost_price_adjustment_id . '/edit')
                    : null,
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $positive_total = $rows->filter(fn ($row) => $row->total_adjustment_amount > 0)->sum('total_adjustment_amount');
        $negative_total = $rows->filter(fn ($row) => $row->total_adjustment_amount < 0)->sum('total_adjustment_amount');

        $totals = [
            'positive_total' => currency(round($positive_total, 2)),
            'negative_total' => currency(round($negative_total, 2)),
            'net_total'      => currency(round($positive_total + $negative_total, 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('adjustment_date', fn ($row) => $row->adjustment_date ? businessDate($row->adjustment_date) : 'N/A')
            ->addColumn('reference_no', function ($row) {
                return $row->detail_url
                    ? '<a href="' . e($row->detail_url) . '">' . e($row->reference_no) . '</a>'
                    : e($row->reference_no);
            })
            ->addColumn('product_name', fn ($row) => e($row->product_name))
            ->addColumn('variation_name', fn ($row) => e($row->variation_name))
            ->addColumn('warehouse_name', fn ($row) => e($row->warehouse_name))
            ->addColumn('branch_name', fn ($row) => e($row->branch_name ?? '-'))
            ->addColumn('previous_cost_price', fn ($row) => currency($row->previous_cost_price))
            ->addColumn('new_cost_price', fn ($row) => currency($row->new_cost_price))
            ->addColumn('quantity_on_hand', fn ($row) => decimal($row->quantity_on_hand))
            ->addColumn('difference_per_unit', fn ($row) => currency($row->difference_per_unit))
            ->addColumn('total_adjustment_amount', fn ($row) => currency($row->total_adjustment_amount))
            ->addColumn('reason', fn ($row) => e($row->reason))
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->addColumn('created_by_name', fn ($row) => e($row->created_by_name ?? '-'))
            ->addColumn('approved_by_name', fn ($row) => e($row->approved_by_name ?? '-'))
            ->addColumn('journal_entry_no', function ($row) {
                return $row->journal_entry_no
                    ? '<button type="button" class="btn btn-link p-0 view-jv-btn" data-source-type="'
                        . e(JournalSourceTypes::INVENTORY_COST_ADJUSTMENT)
                        . '" data-source-id="' . e($row->cost_price_adjustment_id) . '">'
                        . e($row->journal_entry_no)
                        . '</button>'
                    : '-';
            })
            ->rawColumns(['reference_no', 'journal_entry_no'])
            ->with($totals)
            ->make(true);
    }
}
