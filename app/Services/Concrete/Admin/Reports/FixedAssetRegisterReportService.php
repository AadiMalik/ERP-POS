<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\DepreciationFrequencies;
use App\Enums\FixedAssetStatuses;
use App\Enums\RoleNames;
use App\Models\FixedAsset;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class FixedAssetRegisterReportService
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
        $query = FixedAsset::with(['category', 'branch', 'business'])
            ->where('is_deleted', 0);

        $business_id = $obj['business_id'] ?? Auth::user()->business_id;
        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($obj['branch_id'])) {
            $query->where('branch_id', $obj['branch_id']);
        }
        if (!empty($obj['fixed_asset_category_id'])) {
            $query->where('fixed_asset_category_id', $obj['fixed_asset_category_id']);
        }
        if (!empty($obj['depreciation_status'])) {
            $query->where('depreciation_status', $obj['depreciation_status']);
        }
        if (!empty($obj['start_date'])) {
            $query->where('purchase_date', '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }
        if (!empty($obj['end_date'])) {
            $query->where('purchase_date', '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        $query = applyRoleScope($query, $this->allow_roles);

        $labels = FixedAssetStatuses::labels();
        $freqLabels = DepreciationFrequencies::labels();

        return $query->orderBy('asset_code')->get()->map(function ($item) use ($labels, $freqLabels) {
            return (object) [
                'asset_code' => $item->asset_code,
                'name' => $item->name,
                'category' => $item->category->name ?? '',
                'branch' => $item->branch->name ?? '',
                'purchase_date' => $item->purchase_date,
                'purchase_cost' => (float) $item->purchase_cost,
                'current_book_value' => (float) $item->current_book_value,
                'accumulated_depreciation' => (float) $item->accumulated_depreciation,
                'residual_value' => (float) $item->residual_value,
                'depreciation_frequency' => $freqLabels[$item->depreciation_frequency] ?? $item->depreciation_frequency,
                'depreciation_status' => $labels[$item->depreciation_status] ?? $item->depreciation_status,
                'next_depreciation_date' => $item->next_depreciation_date,
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        return DataTables::of($rows)
            ->addColumn('purchase_date', fn ($row) => $row->purchase_date ? localDate($row->purchase_date) : '')
            ->addColumn('purchase_cost', fn ($row) => currency($row->purchase_cost))
            ->addColumn('current_book_value', fn ($row) => currency($row->current_book_value))
            ->addColumn('accumulated_depreciation', fn ($row) => currency($row->accumulated_depreciation))
            ->addColumn('residual_value', fn ($row) => currency($row->residual_value))
            ->addColumn('next_depreciation_date', fn ($row) => $row->next_depreciation_date ? localDate($row->next_depreciation_date) : '')
            ->make(true);
    }
}
