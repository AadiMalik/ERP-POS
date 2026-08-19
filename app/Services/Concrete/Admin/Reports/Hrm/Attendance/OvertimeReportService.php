<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Attendance;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * OT hours = working_hours beyond the employee's shift standard daily hours,
 * OT amount = OT hours x the employee's active salary structure overtime
 * rate - the same formula AttendanceService::monthlySummary() uses for
 * payroll generation, applied per attendance row instead of aggregated.
 */
class OvertimeReportService extends BaseAttendanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        $start = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : Carbon::now()->startOfMonth();
        $end = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : Carbon::now()->endOfDay();

        $query = Attendance::with(['employee.user', 'employee.department', 'employee.shift', 'employee.activeSalaryStructure'])
            ->where('is_deleted', 0)
            ->whereNotNull('working_hours')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }

        $query = $this->scope($query);

        return $query->get()->map(function ($attendance) {
            $shift = $attendance->employee?->shift;
            $standardHours = 8.0;

            if ($shift) {
                $shiftStart = Carbon::parse($shift->start_time);
                $shiftEnd = Carbon::parse($shift->end_time);
                if ($shiftEnd->lt($shiftStart)) {
                    $shiftEnd->addDay();
                }
                $minutes = $shiftStart->diffInMinutes($shiftEnd) - ($shift->break_duration_minutes ?? 0);
                $standardHours = max(0, $minutes) / 60;
            }

            $otHours = round(max(0, $attendance->working_hours - $standardHours), 2);
            $otRate = $attendance->employee?->activeSalaryStructure?->overtime_rate_per_hour ?? 0;

            $attendance->ot_hours = $otHours;
            $attendance->ot_rate = $otRate;
            $attendance->ot_amount = round($otHours * $otRate, 2);

            return $attendance;
        })->filter(fn ($row) => $row->ot_hours > 0)->values();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = [
            'total_ot_hours' => round($rows->sum('ot_hours'), 2),
            'total_ot_amount' => currency(round($rows->sum('ot_amount'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('date', fn ($row) => localDate($row->date))
            ->addColumn('ot_amount', fn ($row) => currency($row->ot_amount))
            ->with($totals)
            ->make(true);
    }
}
