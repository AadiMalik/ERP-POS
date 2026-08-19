<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\PayrollRun;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class PayrollSummaryReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = PayrollRun::with(['payslips'])->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['month'])) {
            $query->where('month', $filters['month']);
        }
        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query = $this->scope($query);

        return $query->orderBy('year', 'desc')->orderBy('month', 'desc')->get()->map(function ($run) {
            $run->employee_count = $run->payslips->count();
            $run->total_gross = round($run->payslips->sum('total_earnings'), 2);
            $run->total_deductions = round($run->payslips->sum('total_deductions'), 2);
            $run->total_advance_deduction = round($run->payslips->sum('advance_deduction'), 2);
            $run->total_overtime = round($run->payslips->sum('overtime_amount'), 2);
            $run->total_net_salary = round($run->payslips->sum('net_salary'), 2);

            return $run;
        });
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = ['total_net_salary' => currency($rows->sum('total_net_salary'))];

        return DataTables::of($rows)
            ->addColumn('period', fn ($row) => date('F', mktime(0, 0, 0, $row->month, 1)) . ' ' . $row->year)
            ->addColumn('total_gross', fn ($row) => currency($row->total_gross))
            ->addColumn('total_deductions', fn ($row) => currency($row->total_deductions))
            ->addColumn('total_advance_deduction', fn ($row) => currency($row->total_advance_deduction))
            ->addColumn('total_overtime', fn ($row) => currency($row->total_overtime))
            ->addColumn('total_net_salary', fn ($row) => currency($row->total_net_salary))
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->with($totals)
            ->make(true);
    }
}
