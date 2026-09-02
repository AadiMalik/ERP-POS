<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Models\FixedAssetDepreciation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class DepreciationReportService
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
        $query = FixedAssetDepreciation::with(['fixedAsset', 'branch', 'journalEntry'])
            ->where('is_deleted', 0);

        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($obj['branch_id'])) {
            $query->where('branch_id', $obj['branch_id']);
        }
        if (!empty($obj['fixed_asset_id'])) {
            $query->where('fixed_asset_id', $obj['fixed_asset_id']);
        }
        if (!empty($obj['start_date'])) {
            $query->where('depreciation_date', '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }
        if (!empty($obj['end_date'])) {
            $query->where('depreciation_date', '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        $query = applyRoleScope($query, $this->allow_roles);

        return $query->orderBy('depreciation_date')->get()->map(function ($item) {
            return (object) [
                'depreciation_date' => $item->depreciation_date,
                'period_key' => $item->period_key,
                'asset_code' => $item->fixedAsset->asset_code ?? '',
                'asset_name' => $item->fixedAsset->name ?? '',
                'branch' => $item->branch->name ?? '',
                'previous_value' => (float) $item->previous_value,
                'depreciation_amount' => (float) $item->depreciation_amount,
                'new_value' => (float) $item->new_value,
                'accumulated_depreciation' => (float) $item->accumulated_depreciation,
                'journal_entry' => $item->journalEntry->entry_no ?? ($item->journal_entry_id ?? ''),
                'status' => $item->status,
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'total_depreciation_amount' => currency(round($rows->sum('depreciation_amount'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('depreciation_date', fn ($row) => $row->depreciation_date ? localDate($row->depreciation_date) : '')
            ->addColumn('previous_value', fn ($row) => currency($row->previous_value))
            ->addColumn('depreciation_amount', fn ($row) => currency($row->depreciation_amount))
            ->addColumn('new_value', fn ($row) => currency($row->new_value))
            ->addColumn('accumulated_depreciation', fn ($row) => currency($row->accumulated_depreciation))
            ->with($totals)
            ->make(true);
    }
}
