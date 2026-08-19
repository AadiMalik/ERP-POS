<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\PayrollRun;
use App\Models\PayslipItem;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Per-period payroll cost trend (PayrollRun.total_amount) with a
 * component-type breakdown (earning vs deduction, sourced from
 * PayslipItem.type) for the periods in range.
 */
class PayrollCostReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = PayrollRun::with(['payslips.items'])->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        $query = $this->scope($query);

        return $query->orderBy('year')->orderBy('month')->get()->map(function ($run) {
            $payslipIds = $run->payslips->pluck('payslip_id');
            $items = PayslipItem::whereIn('payslip_id', $payslipIds)->get();

            return (object) [
                'period' => date('F', mktime(0, 0, 0, $run->month, 1)) . ' ' . $run->year,
                'employee_count' => $run->payslips->count(),
                'total_earnings' => round($items->where('type', 'earning')->sum('amount'), 2),
                'total_deductions' => round($items->where('type', 'deduction')->sum('amount'), 2),
                'total_cost' => round($run->total_amount, 2),
                'status' => $run->status,
            ];
        });
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = ['total_cost' => currency($rows->sum('total_cost'))];

        return DataTables::of($rows)
            ->addColumn('total_earnings', fn ($row) => currency($row->total_earnings))
            ->addColumn('total_deductions', fn ($row) => currency($row->total_deductions))
            ->addColumn('total_cost', fn ($row) => currency($row->total_cost))
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->with($totals)
            ->make(true);
    }
}
