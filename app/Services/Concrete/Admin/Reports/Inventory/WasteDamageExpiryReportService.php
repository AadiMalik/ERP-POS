<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Enums\LossType;
use App\Enums\Status;
use App\Models\WasteDamageExpiry;
use App\Services\Concrete\Admin\Reports\Inventory\Concerns\AppliesInventoryReportScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Dedicated Waste/Damage/Expiry report - reads directly from
 * waste_damage_expiries/details (unlike StockLossReportService, which reads
 * only the stock ledger and therefore only ever shows already-APPROVED
 * write-offs). This report also surfaces pending/cancelled records, batch/
 * expiry detail, loss reason, and the full approval trail.
 */
class WasteDamageExpiryReportService
{
    use AppliesInventoryReportScope;

    protected $with = [
        'business', 'branch', 'warehouse', 'createdby', 'approvedby',
        'details', 'details.product', 'details.productVariation', 'details.unit',
        'details.productVariationBatch', 'details.lossReason',
    ];

    public function build(array $obj): Collection
    {
        $filters = $this->baseFilters($obj);

        $query = WasteDamageExpiry::with($this->with)
            ->where('business_id', $filters['business_id'])
            ->where('is_deleted', 0);

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($obj['created_by'])) {
            $query->where('createdby_id', $obj['created_by']);
        }
        if (!empty($obj['approved_by'])) {
            $query->where('approvedby_id', $obj['approved_by']);
        }
        if (!empty($filters['start_date'])) {
            $query->where('transaction_date', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where('transaction_date', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        $query = applyRoleScope($query, $this->allow_roles);

        $headers = $query->orderByDesc('transaction_date')->get();

        $loss_types = LossType::getOptions();
        $rows = collect();

        foreach ($headers as $header) {
            foreach ($header->details as $detail) {
                if (!empty($filters['product_id']) && $detail->product_id != $filters['product_id']) {
                    continue;
                }
                if (!empty($filters['product_variation_id']) && $detail->product_variation_id != $filters['product_variation_id']) {
                    continue;
                }
                if (!empty($obj['batch_no']) && stripos((string) $detail->batch_no, $obj['batch_no']) === false) {
                    continue;
                }
                if (!empty($obj['loss_type']) && $detail->loss_type != $obj['loss_type']) {
                    continue;
                }
                if (!empty($obj['loss_reason_id']) && $detail->loss_reason_id != $obj['loss_reason_id']) {
                    continue;
                }
                if (!empty($obj['expiry_date']) && (string) $detail->expiry_date !== (string) $obj['expiry_date']) {
                    continue;
                }

                $rows->push((object) [
                    'waste_damage_expiry_id' => $header->waste_damage_expiry_id,
                    'reference_no'           => $header->reference_no,
                    'transaction_date'       => $header->transaction_date,
                    'warehouse_name'         => $header->warehouse->name ?? '-',
                    'product_name'           => $detail->product->name ?? '-',
                    'variation_name'         => $detail->productVariation->name ?? '-',
                    'batch_no'               => $detail->batch_no ?? '-',
                    'expiry_date'            => $detail->expiry_date,
                    'quantity'               => (float) $detail->quantity,
                    'unit_name'              => $detail->unit->name ?? '-',
                    'unit_cost'              => (float) $detail->unit_cost,
                    'value'                  => (float) $detail->value,
                    'loss_type'              => $detail->loss_type,
                    'loss_type_label'        => $loss_types[$detail->loss_type] ?? $detail->loss_type,
                    'loss_reason'            => $detail->lossReason->name ?? '-',
                    'status'                 => $header->status,
                    'created_by'             => $header->createdby->name ?? '-',
                    'approved_by'            => $header->approvedby->name ?? '-',
                    'notes'                  => $detail->notes,
                ]);
            }
        }

        return $rows;
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'total_qty' => decimal(round($rows->sum('quantity'), 3)),
            'total_value' => currency(round($rows->sum('value'), 2)),
        ];

        foreach (LossType::getOptions() as $key => $label) {
            $totals['qty_' . $key] = decimal(round($rows->where('loss_type', $key)->sum('quantity'), 3));
        }

        return DataTables::of($rows)
            ->addColumn('reference_no', function ($row) {
                return '<a href="' . url('/admin/waste-damage-expiry/' . $row->waste_damage_expiry_id . '/edit') . '">' . e($row->reference_no) . '</a>';
            })
            ->addColumn('transaction_date', fn ($row) => localDate($row->transaction_date))
            ->addColumn('warehouse_name', fn ($row) => e($row->warehouse_name))
            ->addColumn('product_name', fn ($row) => e($row->product_name))
            ->addColumn('variation_name', fn ($row) => e($row->variation_name))
            ->addColumn('batch_no', fn ($row) => e($row->batch_no))
            ->addColumn('expiry_date', fn ($row) => $row->expiry_date ? localDate($row->expiry_date) : '-')
            ->addColumn('quantity', fn ($row) => decimal($row->quantity))
            ->addColumn('unit_name', fn ($row) => e($row->unit_name))
            ->addColumn('unit_cost', fn ($row) => currency($row->unit_cost))
            ->addColumn('value', fn ($row) => currency($row->value))
            ->addColumn('loss_type_label', fn ($row) => e($row->loss_type_label))
            ->addColumn('loss_reason', fn ($row) => e($row->loss_reason))
            ->addColumn('status', function ($row) {
                $badges = [
                    Status::PENDING   => 'bg-label-warning',
                    Status::APPROVED  => 'bg-label-success',
                    Status::CANCELLED => 'bg-label-secondary',
                ];
                return '<span class="badge ' . ($badges[$row->status] ?? 'bg-label-secondary') . '">' . ucfirst($row->status) . '</span>';
            })
            ->addColumn('created_by', fn ($row) => e($row->created_by))
            ->addColumn('approved_by', fn ($row) => e($row->approved_by))
            ->rawColumns(['reference_no', 'status'])
            ->with($totals)
            ->make(true);
    }
}
