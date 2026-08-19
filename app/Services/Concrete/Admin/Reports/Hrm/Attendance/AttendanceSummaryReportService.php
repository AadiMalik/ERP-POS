<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Attendance;

use App\Models\Employee;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class AttendanceSummaryReportService extends BaseAttendanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $start = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : Carbon::now()->startOfMonth();
        $end = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : Carbon::now()->endOfDay();

        $query = Employee::with(['user', 'department', 'designation', 'shift', 'attendances' => function ($q) use ($start, $end) {
            $q->whereBetween('date', [$start->toDateString(), $end->toDateString()])->where('is_deleted', 0);
        }])->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
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

        $query = $this->scope($query);

        $period = CarbonPeriod::create($start, $end);

        return $query->orderBy('employee_code')->get()->map(function ($employee) use ($period) {
            $rows = $employee->attendances;
            $workingDays = $employee->shift?->working_days;

            $scheduledDays = 0;
            foreach ($period as $date) {
                if (empty($workingDays) || in_array(strtolower($date->format('l')), $workingDays, true)) {
                    $scheduledDays++;
                }
            }

            return (object) [
                'employee_code' => $employee->employee_code,
                'name' => $employee->user?->name ?? '-',
                'department' => $employee->department?->name ?? '-',
                'designation' => $employee->designation?->name ?? '-',
                'present_count' => $rows->whereIn('status', ['present', 'late', 'half_day'])->count(),
                'absent_count' => $rows->where('status', 'absent')->count(),
                'late_count' => $rows->where('status', 'late')->count(),
                'half_day_count' => $rows->where('status', 'half_day')->count(),
                'leave_count' => $rows->where('status', 'on_leave')->count(),
                'holiday_count' => $rows->where('status', 'holiday')->count(),
                'early_checkout_count' => $rows->where('early_leave_minutes', '>', 0)->count(),
                'total_working_hours' => round($rows->sum('working_hours'), 2),
                'scheduled_working_days' => $scheduledDays,
            ];
        });
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)->make(true);
    }
}
