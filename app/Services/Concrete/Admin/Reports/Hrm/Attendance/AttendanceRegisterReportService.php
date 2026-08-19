<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Attendance;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Matrix-shaped report (employee x date) - rendered server-side as a plain
 * table, same rationale as MonthlyAttendanceReportService but over an
 * arbitrary date range instead of a fixed calendar month.
 */
class AttendanceRegisterReportService extends BaseAttendanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        $start = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : Carbon::now()->startOfMonth();
        $end = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : Carbon::now()->endOfDay();

        $query = Employee::with(['user', 'department', 'attendances' => function ($q) use ($start, $end) {
            $q->whereBetween('date', [$start->toDateString(), $end->toDateString()])->where('is_deleted', 0);
        }])->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
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
                $days[$d->toDateString()] = $attendance ? ($statusCode[$attendance->status] ?? '-') : '';
            }

            return (object) [
                'employee_code' => $employee->employee_code,
                'name' => $employee->user?->name ?? '-',
                'department' => $employee->department?->name ?? '-',
                'days' => $days,
            ];
        });
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)->make(true);
    }

    public function dateRange(array $filters): array
    {
        $start = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : Carbon::now()->startOfMonth();
        $end = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : Carbon::now()->endOfDay();

        $dates = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->copy();
        }

        return $dates;
    }
}
