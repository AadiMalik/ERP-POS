<?php

namespace App\Services\Concrete\Admin\Reports\Hrm;

use App\Enums\RoleNames;
use App\Models\AssetAllocation;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeExit;
use App\Models\ExitClearance;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Standalone widget report - counts/aggregates only, no DataTable/print/
 * PDF/export machinery (doesn't fit the tabular-report shape the other 53
 * reports share).
 */
class HrDashboardReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::HRMANAGER,
        RoleNames::REPORTINGANALYST,
    ];

    public function build(array $filters): array
    {
        $business_id = $filters['business_id'] ?? Auth::user()->business_id;
        $today = Carbon::today()->toDateString();

        $employeeQuery = fn () => applyRoleScope(Employee::where('is_deleted', 0), $this->allow_roles)
            ->when($business_id, fn ($q) => $q->where('business_id', $business_id));

        $totalEmployees = $employeeQuery()->count();
        $activeEmployees = $employeeQuery()->where('status', 'active')->count();
        $onLeaveEmployees = $employeeQuery()->where('status', 'on_leave')->count();
        $newJoiners = $employeeQuery()->whereMonth('joining_date', now()->month)->whereYear('joining_date', now()->year)->count();

        $exitQuery = fn () => applyRoleScope(EmployeeExit::where('is_deleted', 0), $this->allow_roles)
            ->when($business_id, fn ($q) => $q->where('business_id', $business_id))
            ->whereMonth('date_created', now()->month)->whereYear('date_created', now()->year);
        $resignationsThisMonth = (clone $exitQuery())->where('type', 'resignation')->count();
        $terminationsThisMonth = (clone $exitQuery())->where('type', 'termination')->count();

        $attendanceQuery = applyRoleScope(Attendance::where('is_deleted', 0)->where('date', $today), $this->allow_roles)
            ->when($business_id, fn ($q) => $q->where('business_id', $business_id))
            ->get();
        $presentToday = $attendanceQuery->whereIn('status', ['present', 'late', 'half_day'])->count();
        $absentToday = $attendanceQuery->where('status', 'absent')->count();
        $lateToday = $attendanceQuery->where('status', 'late')->count();
        $onLeaveToday = $attendanceQuery->where('status', 'on_leave')->count();

        $pendingLeaveApprovals = applyRoleScope(LeaveRequest::where('is_deleted', 0)->where('status', 'pending'), $this->allow_roles)
            ->when($business_id, fn ($q) => $q->where('business_id', $business_id))
            ->count();

        $latestPayrollRun = applyRoleScope(PayrollRun::where('is_deleted', 0), $this->allow_roles)
            ->when($business_id, fn ($q) => $q->where('business_id', $business_id))
            ->orderBy('year', 'desc')->orderBy('month', 'desc')->first();

        $outstandingAdvances = applyRoleScope(EmployeeAdvance::where('is_deleted', 0)->where('status', 'repaying'), $this->allow_roles)
            ->when($business_id, fn ($q) => $q->where('business_id', $business_id))
            ->sum('remaining_balance');

        $pendingClearances = ExitClearance::whereHas('employeeExit', function ($q) use ($business_id) {
            $q->where('is_deleted', 0);
            if ($business_id) {
                $q->where('business_id', $business_id);
            }
        })->where('status', 'pending')->count();

        $activeAssetAllocations = applyRoleScope(AssetAllocation::where('status', 'issued'), $this->allow_roles)
            ->when($business_id, fn ($q) => $q->where('business_id', $business_id))
            ->count();

        return [
            'total_employees' => $totalEmployees,
            'active_employees' => $activeEmployees,
            'on_leave_employees' => $onLeaveEmployees,
            'new_joiners' => $newJoiners,
            'resignations_this_month' => $resignationsThisMonth,
            'terminations_this_month' => $terminationsThisMonth,
            'present_today' => $presentToday,
            'absent_today' => $absentToday,
            'late_today' => $lateToday,
            'on_leave_today' => $onLeaveToday,
            'pending_leave_approvals' => $pendingLeaveApprovals,
            'latest_payroll_period' => $latestPayrollRun ? (date('F', mktime(0, 0, 0, $latestPayrollRun->month, 1)) . ' ' . $latestPayrollRun->year) : null,
            'latest_payroll_status' => $latestPayrollRun?->status,
            'outstanding_advances' => round($outstandingAdvances, 2),
            'pending_clearances' => $pendingClearances,
            'active_asset_allocations' => $activeAssetAllocations,
        ];
    }
}
