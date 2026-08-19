<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Leave;

use App\Enums\Status;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Employee-centric balance lookup: every active employee x active leave type
 * combination (unlike Leave Summary Report, which only lists combinations
 * that already have at least one request) so HR can see full entitlement
 * even for employees who haven't taken leave yet.
 */
class LeaveBalanceReportService extends BaseLeaveReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        $year = $filters['year'] ?? now()->year;

        $employeeQuery = Employee::with(['user', 'department'])->where('is_deleted', 0);
        if (!empty($business_id)) {
            $employeeQuery->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $employeeQuery->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['department_id'])) {
            $employeeQuery->where('department_id', $filters['department_id']);
        }
        $employees = $this->scope($employeeQuery)->get();

        $leaveTypeQuery = LeaveType::where('is_deleted', 0)->where('status', Status::ACTIVE);
        if (!empty($business_id)) {
            $leaveTypeQuery->where('business_id', $business_id);
        }
        if (!empty($filters['leave_type_id'])) {
            $leaveTypeQuery->where('leave_type_id', $filters['leave_type_id']);
        }
        $leaveTypes = $leaveTypeQuery->get();

        $employeeIds = $employees->pluck('employee_id')->all();
        $leaveTypeIds = $leaveTypes->pluck('leave_type_id')->all();

        $used = LeaveRequest::where('is_deleted', 0)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('leave_type_id', $leaveTypeIds)
            ->get()
            ->groupBy(fn ($row) => $row->employee_id . '|' . $row->leave_type_id)
            ->map(fn ($group) => $group->sum('days_count'));

        $rows = collect();
        foreach ($employees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                $usedDays = $used->get($employee->employee_id . '|' . $leaveType->leave_type_id, 0);
                $entitlement = $leaveType->max_days_per_year ?? 0;

                $rows->push((object) [
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->user?->name ?? '-',
                    'department' => $employee->department?->name ?? '-',
                    'leave_type' => $leaveType->name,
                    'entitlement' => $entitlement,
                    'used' => $usedDays,
                    'remaining' => max(0, $entitlement - $usedDays),
                ]);
            }
        }

        return $rows;
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)->make(true);
    }
}
