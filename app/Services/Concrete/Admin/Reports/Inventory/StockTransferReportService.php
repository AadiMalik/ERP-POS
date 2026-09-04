<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Models\TransferNote;
use App\Services\Concrete\Admin\Reports\Inventory\Concerns\AppliesInventoryReportScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class StockTransferReportService
{
    use AppliesInventoryReportScope;

    public function build(array $obj): Collection
    {
        $filters = $this->baseFilters($obj);

        $q = TransferNote::with(['sourceWarehouse', 'destinationWarehouse', 'branch', 'destinationBranch'])
            ->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $q->where('business_id', $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $q->where(function ($w) use ($filters) {
                $w->where('branch_id', $filters['branch_id'])
                    ->orWhere('destination_branch_id', $filters['branch_id']);
            });
        }
        if (!empty($obj['source_warehouse_id'])) {
            $q->where('source_warehouse_id', $obj['source_warehouse_id']);
        }
        if (!empty($obj['destination_warehouse_id'])) {
            $q->where('destination_warehouse_id', $obj['destination_warehouse_id']);
        }
        if (!empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $q->where('transfer_note_date', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $q->where('transfer_note_date', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        $q = applyRoleScope($q, $filters['allow_roles']);

        return $q->orderByDesc('transfer_note_date')->get()->map(function ($row) {
            return (object) [
                'transfer_note_id' => $row->transfer_note_id,
                'transfer_note_no' => $row->transfer_note_no,
                'transfer_note_date' => $row->transfer_note_date,
                'source_warehouse' => $row->sourceWarehouse?->name ?? '-',
                'destination_warehouse' => $row->destinationWarehouse?->name ?? '-',
                'source_branch' => $row->branch?->name ?? '-',
                'destination_branch' => $row->destinationBranch?->name ?? '-',
                'total_quantity' => (float) $row->total_quantity,
                'total_value' => (float) $row->total_value,
                'status' => $row->status,
                'edit_url' => url('/admin/transfer-note/' . $row->transfer_note_id . '/edit'),
                'ledger_url' => url('/admin/reports/stock-ledger') . '?' . http_build_query([
                    'reference_type' => 'stock_transfer',
                    'business_id' => $row->business_id,
                ]),
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);
        $totals = [
            'total_qty' => decimal(round($rows->sum('total_quantity'), 3)),
            'total_value' => currency(round($rows->sum('total_value'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('transfer_note_no', fn ($row) => '<a href="' . e($row->edit_url) . '">' . e($row->transfer_note_no) . '</a>')
            ->addColumn('transfer_note_date', fn ($row) => localDate($row->transfer_note_date))
            ->addColumn('source_warehouse', fn ($row) => e($row->source_warehouse))
            ->addColumn('destination_warehouse', fn ($row) => e($row->destination_warehouse))
            ->addColumn('source_branch', fn ($row) => e($row->source_branch))
            ->addColumn('destination_branch', fn ($row) => e($row->destination_branch))
            ->addColumn('total_quantity', fn ($row) => decimal($row->total_quantity))
            ->addColumn('total_value', fn ($row) => currency($row->total_value))
            ->addColumn('status', fn ($row) => e(ucfirst($row->status)))
            ->rawColumns(['transfer_note_no'])
            ->with($totals)
            ->make(true);
    }
}
