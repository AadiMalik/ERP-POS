<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\LeaveRequest;
use App\Models\Payslip;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Maps unpaid approved leave (LeaveType.is_paid = 0) taken within a payroll
 * period to its payroll deduction impact - same per-day-rate formula
 * PayrollService::buildPayslip() uses (basic_salary / days_in_month).
 */
class LeavePayrollImpactReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = Payslip::with(['employee.user', 'employee.department', 'payrollRun'])
            ->whereHas('payrollRun', fn ($q) => $q->where('is_deleted', 0));

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }
        if (!empty($filters['month'])) {
            $query->whereHas('payrollRun', fn ($q) => $q->where('month', $filters['month']));
        }
        if (!empty($filters['year'])) {
            $query->whereHas('payrollRun', fn ($q) => $q->where('year', $filters['year']));
        }

        $query = $this->scope($query);

        return $query->get()->map(function ($payslip) {
            $run = $payslip->payrollRun;
            $unpaidLeaveDays = 0;

            if ($run) {
                $unpaidLeaveDays = LeaveRequest::where('employee_id', $payslip->employee_id)
                    ->where('is_deleted', 0)
                    ->where('status', 'approved')
                    ->whereYear('start_date', $run->year)
                    ->whereMonth('start_date', $run->month)
                    ->whereHas('leaveType', fn ($q) => $q->where('is_paid', 0))
                    ->sum('days_count');
            }

            $daysInMonth = $run ? (int) date('t', mktime(0, 0, 0, $run->month, 1, $run->year)) : 30;
            $perDayRate = $daysInMonth > 0 ? round($payslip->basic_salary / $daysInMonth, 2) : 0;

            $payslip->unpaid_leave_days = $unpaidLeaveDays;
            $payslip->estimated_impact = round($unpaidLeaveDays * $perDayRate, 2);

            return $payslip;
        })->filter(fn ($row) => $row->unpaid_leave_days > 0)->values();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = ['total_impact' => currency($rows->sum('estimated_impact'))];

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('period', fn ($row) => $row->payrollRun ? (date('F', mktime(0, 0, 0, $row->payrollRun->month, 1)) . ' ' . $row->payrollRun->year) : '-')
            ->addColumn('estimated_impact', fn ($row) => currency($row->estimated_impact))
            ->addColumn('net_salary', fn ($row) => currency($row->net_salary))
            ->with($totals)
            ->make(true);
    }
}
