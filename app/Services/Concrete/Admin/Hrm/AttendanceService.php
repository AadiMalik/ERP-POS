<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Enums\Filter;
use App\Models\Attendance;
use App\Models\Employee;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Single source of truth for check-in/out and late/early/working-hours math -
 * shared by the Admin AttendanceController (manual corrections) and the ESS
 * check-in/out controller, plus monthlySummary() which PayrollService reads
 * when generating a run.
 */
class AttendanceService
{
    protected $model_attendance;

    public function __construct()
    {
        $this->model_attendance = new Repository(new Attendance());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['employee_id']) && $obj['employee_id'] != "") {
            $wh[] = ['employee_id', $obj['employee_id']];
        }
        if (isset($obj['status']) && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date', '>=', Carbon::parse($obj['start_date'])->toDateString()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['date', '<=', Carbon::parse($obj['end_date'])->toDateString()];
        }

        $datatable = $this->model_attendance->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->with(['employee.user'])
            ->orderBy('date', $orderBy === Filter::ORDERBY ? 'desc' : $orderBy);
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('employee', function ($item) {
                return $item->employee?->user?->name ?? '-';
            })
            ->addColumn('status', function ($item) {
                $map = ['present' => 'success', 'late' => 'warning', 'half_day' => 'info', 'absent' => 'danger', 'on_leave' => 'secondary', 'holiday' => 'primary'];
                $color = $map[$item->status] ?? 'secondary';
                return '<span class="badge bg-label-' . $color . '">' . ucfirst(str_replace('_', ' ', $item->status)) . '</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('attendance.edit', $item->attendance_id) . "'>
                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteAttendance'
                    data-id='{$item->attendance_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Manual create/edit by HR (any status, any date - used for corrections
     * and for marking absent/holiday days that never had a check-in).
     */
    public function save($obj)
    {
        $employee = Employee::findOrFail($obj['employee_id']);
        $obj['business_id'] = $employee->business_id;
        $obj['branch_id'] = $employee->branch_id;
        $obj['source'] = 'manual';

        if (!empty($obj['check_in_time']) && !empty($obj['check_out_time'])) {
            $obj['working_hours'] = $this->diffInHours($obj['date'], $obj['check_in_time'], $obj['check_out_time']);
        }

        if (!empty($obj['attendance_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model_attendance->update($obj, $obj['attendance_id']);
            return $this->model_attendance->find($obj['attendance_id']);
        }

        $obj['attendance_id'] = generateUuid();
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        return $this->model_attendance->create($obj);
    }

    public function getById($attendance_id)
    {
        return $this->model_attendance->getModel()::with(['employee.user'])->findOrFail($attendance_id);
    }

    public function delete($attendance_id)
    {
        return $this->model_attendance->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $attendance_id);
    }

    public function today($employee_id)
    {
        return $this->model_attendance->getModel()::where('employee_id', $employee_id)
            ->where('date', Carbon::today()->toDateString())
            ->where('is_deleted', 0)
            ->first();
    }

    public function checkIn($employee_id)
    {
        $employee = Employee::with('shift')->findOrFail($employee_id);
        $today = Carbon::today()->toDateString();
        $existing = $this->today($employee_id);

        if ($existing && $existing->check_in_time) {
            throw new Exception('You have already checked in today.');
        }

        $now = Carbon::now();
        $status = 'present';
        $late_minutes = 0;

        if ($employee->shift) {
            $shift_start = Carbon::parse($today . ' ' . $employee->shift->start_time);
            $grace_deadline = $shift_start->copy()->addMinutes($employee->shift->grace_period_minutes ?? 0);
            if ($now->gt($grace_deadline)) {
                $late_minutes = $shift_start->diffInMinutes($now);
                $status = 'late';
            }
        }

        $data = [
            'employee_id' => $employee_id,
            'date' => $today,
            'check_in_time' => $now->format('H:i:s'),
            'status' => $status,
            'late_minutes' => $late_minutes,
            'source' => 'self',
            'business_id' => $employee->business_id,
            'branch_id' => $employee->branch_id,
        ];

        if ($existing) {
            $data['updatedby_id'] = Auth::id();
            $data['date_updated'] = now();
            $this->model_attendance->update($data, $existing->attendance_id);
            return $this->model_attendance->find($existing->attendance_id);
        }

        $data['attendance_id'] = generateUuid();
        $data['createdby_id'] = Auth::id();
        $data['date_created'] = now();
        return $this->model_attendance->create($data);
    }

    public function checkOut($employee_id)
    {
        $employee = Employee::with('shift')->findOrFail($employee_id);
        $today = Carbon::today()->toDateString();
        $existing = $this->today($employee_id);

        if (!$existing || !$existing->check_in_time) {
            throw new Exception('You have not checked in today.');
        }
        if ($existing->check_out_time) {
            throw new Exception('You have already checked out today.');
        }

        $now = Carbon::now();
        $working_hours = $this->diffInHours($today, $existing->check_in_time, $now->format('H:i:s'));
        $early_leave_minutes = 0;

        if ($employee->shift) {
            $shift_end = Carbon::parse($today . ' ' . $employee->shift->end_time);
            if ($now->lt($shift_end)) {
                $early_leave_minutes = $now->diffInMinutes($shift_end);
            }
        }

        $this->model_attendance->update([
            'check_out_time' => $now->format('H:i:s'),
            'working_hours' => $working_hours,
            'early_leave_minutes' => $early_leave_minutes,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $existing->attendance_id);

        return $this->model_attendance->find($existing->attendance_id);
    }

    protected function diffInHours($date, $start_time, $end_time)
    {
        $start = Carbon::parse($date . ' ' . $start_time);
        $end = Carbon::parse($date . ' ' . $end_time);
        if ($end->lt($start)) {
            $end->addDay();
        }
        return round($start->diffInMinutes($end) / 60, 2);
    }

    /**
     * Present/absent/leave/late day counts + total & overtime hours for one
     * employee in one calendar month - consumed directly by
     * PayrollService::generate().
     */
    public function monthlySummary($employee_id, $year, $month)
    {
        $employee = Employee::with('shift')->find($employee_id);

        $standard_daily_hours = 8.0;
        if ($employee && $employee->shift) {
            $start = Carbon::parse($employee->shift->start_time);
            $end = Carbon::parse($employee->shift->end_time);
            if ($end->lt($start)) {
                $end->addDay();
            }
            $minutes = $start->diffInMinutes($end) - ($employee->shift->break_duration_minutes ?? 0);
            $standard_daily_hours = max(0, $minutes) / 60;
        }

        $rows = $this->model_attendance->getModel()::where('employee_id', $employee_id)
            ->where('is_deleted', 0)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $overtime_hours = 0;
        foreach ($rows as $row) {
            $overtime_hours += max(0, $row->working_hours - $standard_daily_hours);
        }

        return [
            'present_days' => $rows->whereIn('status', ['present', 'late', 'half_day'])->count(),
            'absent_days' => $rows->where('status', 'absent')->count(),
            'leave_days' => $rows->where('status', 'on_leave')->count(),
            'late_days' => $rows->where('status', 'late')->count(),
            'total_working_hours' => round($rows->sum('working_hours'), 2),
            'overtime_hours' => round($overtime_hours, 2),
            'standard_daily_hours' => round($standard_daily_hours, 2),
        ];
    }
}
