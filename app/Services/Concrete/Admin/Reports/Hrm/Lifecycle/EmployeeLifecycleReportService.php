<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Lifecycle;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeExit;
use App\Models\EmployeeSalaryStructure;
use App\Models\LeaveRequest;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Single-employee, multi-section timeline report (special-case: not a
 * flat list like every other report - build() returns a Collection of one
 * object so it still satisfies the shared base class contract, and the
 * index view renders it directly rather than through the DataTables
 * partial). No dept/designation change-history table exists in this
 * codebase, so this shows the employee's *current* department/designation
 * only, not a transfer trail.
 */
class EmployeeLifecycleReportService extends BaseLifecycleReportService
{
    public function build(array $filters): Collection
    {
        if (empty($filters['employee_id'])) {
            return collect();
        }

        $business_id = $this->resolveBusinessId($filters);

        $employeeQuery = Employee::with(['user', 'department', 'designation', 'shift', 'activeSalaryStructure'])
            ->where('employee_id', $filters['employee_id'])
            ->where('is_deleted', 0);
        if (!empty($business_id)) {
            $employeeQuery->where('business_id', $business_id);
        }
        $employee = $this->scope($employeeQuery)->first();

        if (!$employee) {
            return collect();
        }

        $attendance = Attendance::where('employee_id', $employee->employee_id)->where('is_deleted', 0)->get();
        $leaveRequests = LeaveRequest::with('leaveType')->where('employee_id', $employee->employee_id)->where('is_deleted', 0)->orderBy('start_date', 'desc')->get();
        $salaryHistory = EmployeeSalaryStructure::with('items.component')->where('employee_id', $employee->employee_id)->where('is_deleted', 0)->orderBy('effective_from')->get();
        $advances = EmployeeAdvance::where('employee_id', $employee->employee_id)->where('is_deleted', 0)->orderBy('request_date', 'desc')->get();
        $exit = EmployeeExit::with(['clearances', 'approver'])->where('employee_id', $employee->employee_id)->where('is_deleted', 0)->orderBy('date_created', 'desc')->first();

        return collect([(object) [
            'employee' => $employee,
            'attendance_present' => $attendance->whereIn('status', ['present', 'late', 'half_day'])->count(),
            'attendance_absent' => $attendance->where('status', 'absent')->count(),
            'attendance_leave' => $attendance->where('status', 'on_leave')->count(),
            'attendance_late' => $attendance->where('status', 'late')->count(),
            'leave_requests' => $leaveRequests,
            'salary_history' => $salaryHistory,
            'advances' => $advances,
            'exit' => $exit,
        ]]);
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)->make(true);
    }
}
