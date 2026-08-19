<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Total cost per employee = sum(Payslip.total_earnings) - already includes
 * basic salary, structure allowances and overtime (see
 * PayrollService::buildPayslip()) - plus advances disbursed in the same
 * period. Deductions are recoveries, not additional employer cost, so they
 * are excluded.
 */
class EmployeeCostReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        $start = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : Carbon::now()->startOfYear();
        $end = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : Carbon::now()->endOfDay();

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
        $employeeIds = $employees->pluck('employee_id')->all();

        $payslips = Payslip::whereIn('employee_id', $employeeIds)
            ->whereHas('payrollRun', fn ($q) => $q->where('is_deleted', 0)
                ->whereBetween('generated_at', [$start, $end]))
            ->get()
            ->groupBy('employee_id');

        $advances = EmployeeAdvance::whereIn('employee_id', $employeeIds)
            ->where('is_deleted', 0)
            ->whereIn('status', ['repaying', 'completed'])
            ->whereBetween('request_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('employee_id');

        return $employees->map(function ($employee) use ($payslips, $advances) {
            $employeePayslips = $payslips->get($employee->employee_id, collect());
            $employeeAdvances = $advances->get($employee->employee_id, collect());

            $totalEarnings = round($employeePayslips->sum('total_earnings'), 2);
            $totalOvertime = round($employeePayslips->sum('overtime_amount'), 2);
            $totalAdvances = round($employeeAdvances->sum('amount'), 2);

            return (object) [
                'employee_code' => $employee->employee_code,
                'name' => $employee->user?->name ?? '-',
                'department' => $employee->department?->name ?? '-',
                'total_earnings' => $totalEarnings,
                'total_overtime' => $totalOvertime,
                'total_advances' => $totalAdvances,
                'total_cost' => round($totalEarnings + $totalAdvances, 2),
            ];
        })->filter(fn ($row) => $row->total_cost > 0)->values();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = ['total_cost' => currency($rows->sum('total_cost'))];

        return DataTables::of($rows)
            ->addColumn('total_earnings', fn ($row) => currency($row->total_earnings))
            ->addColumn('total_overtime', fn ($row) => currency($row->total_overtime))
            ->addColumn('total_advances', fn ($row) => currency($row->total_advances))
            ->addColumn('total_cost', fn ($row) => currency($row->total_cost))
            ->with($totals)
            ->make(true);
    }
}
