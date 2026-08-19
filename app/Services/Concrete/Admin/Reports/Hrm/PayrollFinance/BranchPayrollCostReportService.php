<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Branch-level rollup of EmployeeCostReportService's per-employee cost
 * calculation (total_earnings + advances disbursed) over a date range.
 */
class BranchPayrollCostReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        $start = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : Carbon::now()->startOfYear();
        $end = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : Carbon::now()->endOfDay();

        $employeeQuery = Employee::with('branch')->where('is_deleted', 0);
        if (!empty($business_id)) {
            $employeeQuery->where('business_id', $business_id);
        }
        if (!empty($filters['branch_id'])) {
            $employeeQuery->where('branch_id', $filters['branch_id']);
        }
        $employees = $this->scope($employeeQuery)->get();
        $employeeIds = $employees->pluck('employee_id')->all();

        $payslips = Payslip::whereIn('employee_id', $employeeIds)
            ->whereHas('payrollRun', fn ($q) => $q->where('is_deleted', 0)->whereBetween('generated_at', [$start, $end]))
            ->get()
            ->groupBy('employee_id');

        $advances = EmployeeAdvance::whereIn('employee_id', $employeeIds)
            ->where('is_deleted', 0)
            ->whereIn('status', ['repaying', 'completed'])
            ->whereBetween('request_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('employee_id');

        return $employees->groupBy(fn ($e) => $e->branch_id ?? 'unassigned')
            ->map(function ($group) use ($payslips, $advances) {
                $branch = $group->first()->branch;
                $totalEarnings = 0;
                $totalAdvances = 0;

                foreach ($group as $employee) {
                    $totalEarnings += $payslips->get($employee->employee_id, collect())->sum('total_earnings');
                    $totalAdvances += $advances->get($employee->employee_id, collect())->sum('amount');
                }

                return (object) [
                    'branch' => $branch?->name ?? 'Unassigned',
                    'employee_count' => $group->count(),
                    'total_earnings' => round($totalEarnings, 2),
                    'total_advances' => round($totalAdvances, 2),
                    'total_cost' => round($totalEarnings + $totalAdvances, 2),
                ];
            })
            ->filter(fn ($row) => $row->total_cost > 0)
            ->values();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = ['total_cost' => currency($rows->sum('total_cost'))];

        return DataTables::of($rows)
            ->addColumn('total_earnings', fn ($row) => currency($row->total_earnings))
            ->addColumn('total_advances', fn ($row) => currency($row->total_advances))
            ->addColumn('total_cost', fn ($row) => currency($row->total_cost))
            ->with($totals)
            ->make(true);
    }
}
