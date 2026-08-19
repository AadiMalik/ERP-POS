<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Employee;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class EmployeeDirectoryReportService extends BaseEmployeeReportService
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
        if (!empty($filters['designation_id'])) {
            $query->where('designation_id', $filters['designation_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $query = $this->scope($query);

        return $query->orderBy('employee_code')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('name', fn ($row) => $row->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->department?->name ?? '-')
            ->addColumn('designation', fn ($row) => $row->designation?->name ?? '-')
            ->addColumn('branch', fn ($row) => $row->branch?->name ?? '-')
            ->addColumn('joining_date', fn ($row) => localDate($row->joining_date))
            ->addColumn('email', fn ($row) => $row->user?->email ?? '-')
            ->addColumn('phone', fn ($row) => $row->user?->phone ?? '-')
            ->addColumn('status', fn ($row) => ucfirst(str_replace('_', ' ', $row->status)))
            ->make(true);
    }
}
