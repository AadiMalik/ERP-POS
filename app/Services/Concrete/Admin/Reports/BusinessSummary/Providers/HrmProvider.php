<?php

namespace App\Services\Concrete\Admin\Reports\BusinessSummary\Providers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryContext;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryInsightFactory as Insight;
use Illuminate\Support\Facades\DB;

class HrmProvider
{
    public function collect(BusinessSummaryContext $context): array
    {
        if (!$context->hasModule('hrm') || !$context->canAny([
            'reports.attendance-summary-report.view',
            'attendance.view',
            'employee.view',
            'leave-request.view',
        ])) {
            return ['kpis' => [], 'insights' => [], 'charts' => []];
        }

        $employeeQuery = Employee::query()
            ->where('is_deleted', 0)
            ->where('business_id', $context->business_id)
            ->when($context->branch_id, fn ($q) => $q->where('branch_id', $context->branch_id));

        $activeEmployees = (clone $employeeQuery)->where('status', 'active')->count();
        $totalEmployees = (clone $employeeQuery)->count();

        $attendance = Attendance::query()
            ->where('is_deleted', 0)
            ->where('business_id', $context->business_id)
            ->when($context->branch_id, fn ($q) => $q->where('branch_id', $context->branch_id))
            ->whereBetween('date', [$context->start_date, $context->end_date]);

        $byStatus = (clone $attendance)
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $late = (int) ($byStatus['late'] ?? 0);
        $absent = (int) ($byStatus['absent'] ?? 0);
        $present = (int) ($byStatus['present'] ?? 0) + $late + (int) ($byStatus['half_day'] ?? 0);
        $onLeave = (int) ($byStatus['on_leave'] ?? 0);
        $early = (clone $attendance)->where('early_leave_minutes', '>', 0)->count();
        $missingPunch = (clone $attendance)
            ->whereNotIn('status', ['holiday', 'on_leave', 'absent'])
            ->where(function ($q) {
                $q->whereNull('check_in_time')->orWhereNull('check_out_time');
            })
            ->count();

        $repeatThreshold = (int) $context->threshold('attendance_repeat_late_count', 3);
        $repeatLate = (clone $attendance)
            ->where('status', 'late')
            ->select('employee_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('employee_id')
            ->having('cnt', '>=', $repeatThreshold)
            ->count();

        $pendingLeave = LeaveRequest::query()
            ->where('is_deleted', 0)
            ->where('business_id', $context->business_id)
            ->when($context->branch_id, fn ($q) => $q->where('branch_id', $context->branch_id))
            ->where('status', 'pending')
            ->count();

        $kpis = [
            'employees' => $activeEmployees,
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
        ];

        $charts = [];
        if (($present + $late + $absent + $onLeave) > 0) {
            $charts['attendance'] = [
                'labels' => [
                    __('reports.business_summary.att_present'),
                    __('reports.business_summary.att_late'),
                    __('reports.business_summary.att_absent'),
                    __('reports.business_summary.att_leave'),
                ],
                'series' => [$present, $late, $absent, $onLeave],
            ];
        }

        $insights = [];

        $insights[] = Insight::make([
            'id' => 'hrm_headcount',
            'module' => 'hrm',
            'severity' => 'informational',
            'icon' => 'fa-users',
            'title_key' => 'reports.business_summary.hrm_headcount_title',
            'title_params' => ['count' => $activeEmployees],
            'description_key' => 'reports.business_summary.hrm_headcount_desc',
            'description_params' => ['total' => $totalEmployees],
            'metric' => $activeEmployees,
            'action' => Insight::action($context, 'reports.business_summary.actions.view_employees', '/admin/employee', [], 'employee.view'),
        ]);

        if ($present + $late + $absent + $onLeave === 0) {
            $insights[] = Insight::make([
                'id' => 'attendance_no_activity',
                'module' => 'hrm',
                'severity' => 'informational',
                'icon' => 'fa-calendar-check',
                'title_key' => 'reports.business_summary.attendance_no_activity_title',
                'description_key' => 'reports.business_summary.no_activity_period',
                'empty' => true,
                'action' => Insight::action($context, 'reports.business_summary.actions.view_attendance', '/admin/reports/attendance-summary-report', [], 'reports.attendance-summary-report.view'),
            ]);
        } else {
            $insights[] = Insight::make([
                'id' => 'attendance_summary',
                'module' => 'hrm',
                'severity' => 'informational',
                'icon' => 'fa-calendar-check',
                'title_key' => 'reports.business_summary.attendance_title',
                'description_key' => 'reports.business_summary.attendance_desc',
                'description_params' => [
                    'present' => $present,
                    'late' => $late,
                    'absent' => $absent,
                    'leave' => $onLeave,
                ],
                'metric' => $present,
                'action' => Insight::action($context, 'reports.business_summary.actions.view_attendance', '/admin/reports/attendance-summary-report', [], 'reports.attendance-summary-report.view'),
            ]);
        }

        if ($late > 0) {
            $insights[] = Insight::make([
                'id' => 'late_arrivals',
                'module' => 'hrm',
                'severity' => $repeatLate > 0 ? 'important' : 'informational',
                'icon' => 'fa-clock',
                'title_key' => 'reports.business_summary.late_title',
                'title_params' => ['count' => $late],
                'description_key' => 'reports.business_summary.late_desc',
                'description_params' => ['repeat' => $repeatLate, 'threshold' => $repeatThreshold],
                'metric' => $late,
                'why_key' => $repeatLate > 0 ? 'reports.business_summary.why.repeat_late' : null,
                'action' => Insight::action($context, 'reports.business_summary.actions.view_late', '/admin/reports/late-attendance-report', [], 'reports.late-attendance-report.view'),
            ]);
        }

        if ($absent > 0) {
            $insights[] = Insight::make([
                'id' => 'absences',
                'module' => 'hrm',
                'severity' => 'important',
                'icon' => 'fa-user-xmark',
                'title_key' => 'reports.business_summary.absent_title',
                'title_params' => ['count' => $absent],
                'description_key' => 'reports.business_summary.absent_desc',
                'metric' => $absent,
                'why_key' => 'reports.business_summary.why.absences',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_absent', '/admin/reports/absent-employees-report', [], 'reports.absent-employees-report.view'),
            ]);
        }

        if ($early > 0) {
            $insights[] = Insight::make([
                'id' => 'early_leave',
                'module' => 'hrm',
                'severity' => 'informational',
                'icon' => 'fa-person-walking-arrow-right',
                'title_key' => 'reports.business_summary.early_title',
                'title_params' => ['count' => $early],
                'description_key' => 'reports.business_summary.early_desc',
                'metric' => $early,
                'action' => Insight::action($context, 'reports.business_summary.actions.view_early', '/admin/reports/early-checkout-report', [], 'reports.early-checkout-report.view'),
            ]);
        }

        if ($missingPunch > 0) {
            $insights[] = Insight::make([
                'id' => 'missing_punch',
                'module' => 'hrm',
                'severity' => 'important',
                'icon' => 'fa-question',
                'title_key' => 'reports.business_summary.missing_punch_title',
                'title_params' => ['count' => $missingPunch],
                'description_key' => 'reports.business_summary.missing_punch_desc',
                'metric' => $missingPunch,
                'why_key' => 'reports.business_summary.why.missing_punch',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_missing_punch', '/admin/reports/missing-checkin-checkout-report', [], 'reports.missing-checkin-checkout-report.view'),
            ]);
        }

        if ($pendingLeave > 0) {
            $insights[] = Insight::make([
                'id' => 'pending_leave',
                'module' => 'hrm',
                'severity' => 'important',
                'icon' => 'fa-umbrella-beach',
                'title_key' => 'reports.business_summary.pending_leave_title',
                'title_params' => ['count' => $pendingLeave],
                'description_key' => 'reports.business_summary.pending_leave_desc',
                'metric' => $pendingLeave,
                'why_key' => 'reports.business_summary.why.pending_leave',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_leaves', '/admin/leave-request', ['status' => 'pending'], 'leave-request.view'),
            ]);
        }

        if ($late === 0 && $absent === 0 && $present > 0) {
            $insights[] = Insight::make([
                'id' => 'attendance_excellent',
                'module' => 'hrm',
                'severity' => 'excellent',
                'icon' => 'fa-star',
                'title_key' => 'reports.business_summary.attendance_excellent_title',
                'description_key' => 'reports.business_summary.attendance_excellent_desc',
                'metric' => $present,
            ]);
        }

        return compact('kpis', 'insights', 'charts');
    }
}
