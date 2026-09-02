<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\FixedAssetDisposalTypes;
use App\Enums\FixedAssetStatuses;
use App\Enums\RoleNames;
use App\Models\FixedAsset;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class AssetDisposalReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
        RoleNames::BRANCHADMIN,
    ];

    public function build(array $obj)
    {
        $query = FixedAsset::with(['category', 'branch', 'disposalJournalEntry'])
            ->where('is_deleted', 0)
            ->whereIn('depreciation_status', [
                FixedAssetStatuses::SOLD,
                FixedAssetStatuses::DISPOSED,
                FixedAssetStatuses::WRITTEN_OFF,
                FixedAssetStatuses::DAMAGED,
            ]);

        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($obj['branch_id'])) {
            $query->where('branch_id', $obj['branch_id']);
        }
        if (!empty($obj['disposal_type'])) {
            $query->where('disposal_type', $obj['disposal_type']);
        }
        if (!empty($obj['start_date'])) {
            $query->where('disposal_date', '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }
        if (!empty($obj['end_date'])) {
            $query->where('disposal_date', '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        $query = applyRoleScope($query, $this->allow_roles);
        $typeLabels = FixedAssetDisposalTypes::labels();
        $statusLabels = FixedAssetStatuses::labels();

        return $query->orderByDesc('disposal_date')->get()->map(function ($item) use ($typeLabels, $statusLabels) {
            return (object) [
                'asset_code' => $item->asset_code,
                'name' => $item->name,
                'category' => $item->category->name ?? '',
                'branch' => $item->branch->name ?? '',
                'disposal_date' => $item->disposal_date,
                'disposal_type' => $typeLabels[$item->disposal_type] ?? $item->disposal_type,
                'sale_price' => (float) ($item->sale_price ?? 0),
                'current_book_value' => (float) $item->current_book_value,
                'purchase_cost' => (float) $item->purchase_cost,
                'accumulated_depreciation' => (float) $item->accumulated_depreciation,
                'disposal_reason' => $item->disposal_reason,
                'depreciation_status' => $statusLabels[$item->depreciation_status] ?? $item->depreciation_status,
                'journal_entry' => $item->disposalJournalEntry->entry_no ?? ($item->disposal_journal_entry_id ?? ''),
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'total_sale_price' => currency(round($rows->sum('sale_price'), 2)),
            'total_book_value' => currency(round($rows->sum('current_book_value'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('disposal_date', fn ($row) => $row->disposal_date ? localDate($row->disposal_date) : '')
            ->addColumn('sale_price', fn ($row) => currency($row->sale_price))
            ->addColumn('current_book_value', fn ($row) => currency($row->current_book_value))
            ->addColumn('purchase_cost', fn ($row) => currency($row->purchase_cost))
            ->addColumn('accumulated_depreciation', fn ($row) => currency($row->accumulated_depreciation))
            ->with($totals)
            ->make(true);
    }
}
