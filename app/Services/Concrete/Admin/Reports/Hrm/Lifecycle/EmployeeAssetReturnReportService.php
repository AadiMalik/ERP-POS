<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Lifecycle;

use App\Models\AssetAllocation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class EmployeeAssetReturnReportService extends BaseLifecycleReportService
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

        $status = $filters['return_status'] ?? 'pending';
        if ($status == 'returned') {
            $query->where('status', 'returned');
        } elseif ($status == 'overdue') {
            $query->where('status', 'issued')->whereDate('expected_return_date', '<', Carbon::today()->toDateString());
        } else {
            $query->where('status', 'issued');
        }

        $query = $this->scope($query);

        return $query->orderBy('expected_return_date')->get()->map(function ($allocation) {
            $allocation->is_overdue = $allocation->status == 'issued'
                && $allocation->expected_return_date
                && Carbon::parse($allocation->expected_return_date)->isPast();

            return $allocation;
        });
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('asset_tag', fn ($row) => $row->asset?->asset_tag ?? '-')
            ->addColumn('asset_name', fn ($row) => $row->asset?->name ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('issue_date', fn ($row) => localDate($row->issue_date))
            ->addColumn('expected_return_date', fn ($row) => $row->expected_return_date ? localDate($row->expected_return_date) : '-')
            ->addColumn('return_date', fn ($row) => $row->return_date ? localDate($row->return_date) : '-')
            ->addColumn('status', fn ($row) => $row->is_overdue ? 'Overdue' : ucfirst($row->status))
            ->make(true);
    }
}
