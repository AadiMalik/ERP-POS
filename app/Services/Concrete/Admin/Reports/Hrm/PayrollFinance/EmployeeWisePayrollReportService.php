<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\Payslip;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class EmployeeWisePayrollReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = Payslip::with(['employee.user', 'employee.department', 'payrollRun'])
            ->whereHas('payrollRun', fn ($q) => $q->where('is_deleted', 0));

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
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
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query = $this->scope($query);

        return $query->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = ['total_net_salary' => currency($rows->sum('net_salary'))];

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('period', fn ($row) => $row->payrollRun ? (date('F', mktime(0, 0, 0, $row->payrollRun->month, 1)) . ' ' . $row->payrollRun->year) : '-')
            ->addColumn('basic_salary', fn ($row) => currency($row->basic_salary))
            ->addColumn('total_earnings', fn ($row) => currency($row->total_earnings))
            ->addColumn('total_deductions', fn ($row) => currency($row->total_deductions))
            ->addColumn('overtime_amount', fn ($row) => currency($row->overtime_amount))
            ->addColumn('advance_deduction', fn ($row) => currency($row->advance_deduction))
            ->addColumn('net_salary', fn ($row) => currency($row->net_salary))
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->with($totals)
            ->make(true);
    }
}
