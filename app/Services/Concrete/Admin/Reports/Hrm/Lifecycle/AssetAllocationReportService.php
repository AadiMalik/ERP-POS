<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Lifecycle;

use App\Models\AssetAllocation;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class AssetAllocationReportService extends BaseLifecycleReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = AssetAllocation::with(['asset', 'employee.user', 'employee.department']);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['asset_category'])) {
            $query->whereHas('asset', fn ($q) => $q->where('category', $filters['asset_category']));
        }

        $query = $this->scope($query);

        return $query->orderBy('issue_date', 'desc')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('asset_tag', fn ($row) => $row->asset?->asset_tag ?? '-')
            ->addColumn('asset_name', fn ($row) => $row->asset?->name ?? '-')
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('issue_date', fn ($row) => localDate($row->issue_date))
            ->addColumn('expected_return_date', fn ($row) => $row->expected_return_date ? localDate($row->expected_return_date) : '-')
            ->addColumn('return_date', fn ($row) => $row->return_date ? localDate($row->return_date) : '-')
            ->addColumn('condition_on_issue', fn ($row) => ucfirst($row->condition_on_issue))
            ->addColumn('condition_on_return', fn ($row) => $row->condition_on_return ? ucfirst($row->condition_on_return) : '-')
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->make(true);
    }
}
