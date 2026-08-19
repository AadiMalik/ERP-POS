<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Attendance;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Matrix-shaped report (employee x day-of-month) - rendered server-side as a
 * plain table rather than through the paginated DataTables partial, since the
 * column count (28-31 days) varies by month. See CLAUDE.md / plan notes on
 * "special-case" reports.
 */
class MonthlyAttendanceReportService extends BaseAttendanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        $month = (int) ($filters['month'] ?? now()->month);
        $year = (int) ($filters['year'] ?? now()->year);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $query = Employee::with(['user', 'department', 'attendances' => function ($q) use ($start, $end) {
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

        $query = $this->scope($query);

        $statusCode = [
            'present' => 'P', 'absent' => 'A', 'late' => 'L', 'half_day' => 'HD',
            'on_leave' => 'LV', 'holiday' => 'H',
        ];

        return $query->orderBy('employee_code')->get()->map(function ($employee) use ($start, $end, $statusCode) {
            $byDate = $employee->attendances->keyBy(fn ($a) => Carbon::parse($a->date)->toDateString());
            $days = [];
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $attendance = $byDate->get($d->toDateString());
                $days[$d->day] = $attendance ? ($statusCode[$attendance->status] ?? '-') : '';
            }

            return (object) [
                'employee_code' => $employee->employee_code,
                'name' => $employee->user?->name ?? '-',
                'department' => $employee->department?->name ?? '-',
                'days' => $days,
                'present_count' => $employee->attendances->whereIn('status', ['present', 'late', 'half_day'])->count(),
                'absent_count' => $employee->attendances->where('status', 'absent')->count(),
                'leave_count' => $employee->attendances->where('status', 'on_leave')->count(),
                'total_working_hours' => round($employee->attendances->sum('working_hours'), 2),
            ];
        });
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)->make(true);
    }

    public function daysInMonth(array $filters): int
    {
        $month = (int) ($filters['month'] ?? now()->month);
        $year = (int) ($filters['year'] ?? now()->year);

        return Carbon::create($year, $month, 1)->daysInMonth;
    }
}
