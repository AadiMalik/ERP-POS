<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Attendance;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class ShiftAssignmentReportService extends BaseAttendanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = Employee::with(['user', 'department', 'shift'])
            ->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['shift_id'])) {
            $query->where('shift_id', $filters['shift_id']);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
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
            ->addColumn('shift', fn ($row) => $row->shift?->name ?? 'Unassigned')
            ->addColumn('timing', fn ($row) => $row->shift ? (date('h:i A', strtotime($row->shift->start_time)) . ' - ' . date('h:i A', strtotime($row->shift->end_time))) : '-')
            ->addColumn('working_days', fn ($row) => $row->shift?->working_days ? implode(', ', array_map('ucfirst', $row->shift->working_days)) : '-')
            ->make(true);
    }
}
