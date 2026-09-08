<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Attendance;

use App\Models\Attendance;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class ShiftWiseAttendanceReportService extends BaseAttendanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        [$start, $end] = $this->calendarRange($filters);

        $query = Attendance::with(['employee.shift'])
            ->where('is_deleted', 0)
            ->whereBetween('date', [$start, $end]);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['shift_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('shift_id', $filters['shift_id']));
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }

        $query = $this->scope($query);

        return $query->get()
            ->groupBy(fn ($row) => $row->employee?->shift?->shift_id ?? 'unassigned')
            ->map(function ($group) {
                $shift = $group->first()->employee?->shift;

                return (object) [
                    'shift_name' => $shift?->name ?? 'Unassigned',
                    'timing' => $shift ? (date('h:i A', strtotime($shift->start_time)) . ' - ' . date('h:i A', strtotime($shift->end_time))) : '-',
                    'employee_count' => $group->pluck('employee_id')->unique()->count(),
                    'present_count' => $group->whereIn('status', ['present', 'late', 'half_day'])->count(),
                    'absent_count' => $group->where('status', 'absent')->count(),
                    'late_count' => $group->where('status', 'late')->count(),
                    'leave_count' => $group->where('status', 'on_leave')->count(),
                    'total_working_hours' => round($group->sum('working_hours'), 2),
                ];
            })
            ->values();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)->make(true);
    }
}
