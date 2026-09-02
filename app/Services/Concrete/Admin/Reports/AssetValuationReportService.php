<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\FixedAssetStatuses;
use App\Enums\RoleNames;
use App\Models\FixedAsset;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class AssetValuationReportService
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
        $query = FixedAsset::with(['category', 'branch'])
            ->where('is_deleted', 0)
            ->whereNotIn('depreciation_status', [
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
        if (!empty($obj['fixed_asset_category_id'])) {
            $query->where('fixed_asset_category_id', $obj['fixed_asset_category_id']);
        }
        if (!empty($obj['start_date'])) {
            $query->where('purchase_date', '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }
        if (!empty($obj['end_date'])) {
            $query->where('purchase_date', '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        $query = applyRoleScope($query, $this->allow_roles);
        $labels = FixedAssetStatuses::labels();

        return $query->orderBy('asset_code')->get()->map(function ($item) use ($labels) {
            return (object) [
                'asset_code' => $item->asset_code,
                'name' => $item->name,
                'category' => $item->category->name ?? '',
                'branch' => $item->branch->name ?? '',
                'purchase_cost' => (float) $item->purchase_cost,
                'accumulated_depreciation' => (float) $item->accumulated_depreciation,
                'current_book_value' => (float) $item->current_book_value,
                'previous_book_value' => (float) $item->previous_book_value,
                'residual_value' => (float) $item->residual_value,
                'depreciation_status' => $labels[$item->depreciation_status] ?? $item->depreciation_status,
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'total_purchase_cost' => currency(round($rows->sum('purchase_cost'), 2)),
            'total_accumulated_depreciation' => currency(round($rows->sum('accumulated_depreciation'), 2)),
            'total_current_book_value' => currency(round($rows->sum('current_book_value'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('purchase_cost', fn ($row) => currency($row->purchase_cost))
            ->addColumn('accumulated_depreciation', fn ($row) => currency($row->accumulated_depreciation))
            ->addColumn('current_book_value', fn ($row) => currency($row->current_book_value))
            ->addColumn('previous_book_value', fn ($row) => currency($row->previous_book_value))
            ->addColumn('residual_value', fn ($row) => currency($row->residual_value))
            ->with($totals)
            ->make(true);
    }
}
