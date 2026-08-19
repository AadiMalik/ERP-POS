<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Attendance;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class DailyAttendanceReportService extends BaseAttendanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        $date = !empty($filters['date']) ? Carbon::parse($filters['date'])->toDateString() : Carbon::today()->toDateString();

        $query = Attendance::with(['employee.user', 'employee.department', 'employee.designation'])
            ->where('is_deleted', 0)
            ->where('date', $date);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query = $this->scope($query);

        return $query->orderBy('check_in_time')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('check_in_time', fn ($row) => $row->check_in_time ?? '-')
            ->addColumn('check_out_time', fn ($row) => $row->check_out_time ?? '-')
            ->addColumn('working_hours', fn ($row) => $row->working_hours ?? '-')
            ->addColumn('late_minutes', fn ($row) => $row->late_minutes ?? 0)
            ->addColumn('early_leave_minutes', fn ($row) => $row->early_leave_minutes ?? 0)
            ->addColumn('status', fn ($row) => ucfirst(str_replace('_', ' ', $row->status)))
            ->make(true);
    }
}
