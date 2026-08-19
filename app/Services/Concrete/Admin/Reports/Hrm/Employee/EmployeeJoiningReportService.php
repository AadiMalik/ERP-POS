<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Employee;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class EmployeeJoiningReportService extends BaseEmployeeReportService
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
        if (!empty($filters['start_date'])) {
            $query->whereDate('joining_date', '>=', Carbon::parse($filters['start_date'])->toDateString());
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('joining_date', '<=', Carbon::parse($filters['end_date'])->toDateString());
        }

        $query = $this->scope($query);

        return $query->orderBy('joining_date', 'desc')->get();
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
            ->addColumn('employment_type', fn ($row) => ucfirst(str_replace('_', ' ', (string) $row->employment_type)))
            ->addColumn('status', fn ($row) => ucfirst(str_replace('_', ' ', $row->status)))
            ->make(true);
    }
}
