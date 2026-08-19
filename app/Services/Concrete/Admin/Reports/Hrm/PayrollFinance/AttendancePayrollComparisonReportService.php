<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\Attendance;
use App\Models\Payslip;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Reconciles the attendance-derived day counts (recomputed live from
 * Attendance rows) against what PayrollService actually stored on the
 * Payslip when the run was generated - flags any mismatch (e.g. attendance
 * corrected after payroll was generated but before it was reopened).
 */
class AttendancePayrollComparisonReportService extends BasePayrollFinanceReportService
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
            $actual = Attendance::where('employee_id', $payslip->employee_id)
                ->where('is_deleted', 0)
                ->whereYear('date', $run->year)
                ->whereMonth('date', $run->month)
                ->get();

            $payslip->actual_present = $actual->whereIn('status', ['present', 'late', 'half_day'])->count();
            $payslip->actual_absent = $actual->where('status', 'absent')->count();
            $payslip->actual_leave = $actual->where('status', 'on_leave')->count();
            $payslip->is_mismatched = $payslip->actual_present != $payslip->present_days
                || $payslip->actual_absent != $payslip->absent_days
                || $payslip->actual_leave != $payslip->leave_days;

            return $payslip;
        });
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('period', fn ($row) => $row->payrollRun ? (date('F', mktime(0, 0, 0, $row->payrollRun->month, 1)) . ' ' . $row->payrollRun->year) : '-')
            ->addColumn('net_salary', fn ($row) => currency($row->net_salary))
            ->addColumn('match_status', fn ($row) => $row->is_mismatched ? 'Mismatch' : 'Matched')
            ->make(true);
    }
}
