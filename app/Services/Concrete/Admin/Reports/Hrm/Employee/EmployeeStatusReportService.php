<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Employee;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class EmployeeStatusReportService extends BaseEmployeeReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = Employee::with(['user', 'department', 'designation', 'branch'])
            ->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query = $this->scope($query);

        return $query->orderBy('status')->orderBy('date_created', 'desc')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = [
            'total_active' => $rows->where('status', EmployeeStatus::ACTIVE)->count(),
            'total_on_leave' => $rows->where('status', EmployeeStatus::ON_LEAVE)->count(),
            'total_suspended' => $rows->where('status', EmployeeStatus::SUSPENDED)->count(),
            'total_resigned' => $rows->where('status', EmployeeStatus::RESIGNED)->count(),
            'total_terminated' => $rows->where('status', EmployeeStatus::TERMINATED)->count(),
        ];

        return DataTables::of($rows)
            ->addColumn('name', fn ($row) => $row->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->department?->name ?? '-')
            ->addColumn('designation', fn ($row) => $row->designation?->name ?? '-')
            ->addColumn('branch', fn ($row) => $row->branch?->name ?? '-')
            ->addColumn('status', fn ($row) => ucfirst(str_replace('_', ' ', $row->status)))
            ->with($totals)
            ->make(true);
    }
}
