<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\Payslip;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class MonthlyPayrollRegisterService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        $month = $filters['month'] ?? now()->month;
        $year = $filters['year'] ?? now()->year;

        $query = Payslip::with(['employee.user', 'employee.department', 'payrollRun'])
            ->whereHas('payrollRun', fn ($q) => $q->where('is_deleted', 0)->where('month', $month)->where('year', $year));

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        $query = $this->scope($query);

        return $query->get()->sortBy(fn ($row) => $row->employee?->employee_code)->values();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = ['total_net_salary' => currency($rows->sum('net_salary'))];

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('basic_salary', fn ($row) => currency($row->basic_salary))
            ->addColumn('total_earnings', fn ($row) => currency($row->total_earnings))
            ->addColumn('total_deductions', fn ($row) => currency($row->total_deductions))
            ->addColumn('net_salary', fn ($row) => currency($row->net_salary))
            ->addColumn('present_days', fn ($row) => $row->present_days)
            ->addColumn('absent_days', fn ($row) => $row->absent_days)
            ->addColumn('leave_days', fn ($row) => $row->leave_days)
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->with($totals)
            ->make(true);
    }
}
