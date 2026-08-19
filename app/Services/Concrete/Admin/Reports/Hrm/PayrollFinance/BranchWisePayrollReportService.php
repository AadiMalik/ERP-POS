<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\Payslip;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class BranchWisePayrollReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = Payslip::with(['branch'])
            ->whereHas('payrollRun', fn ($q) => $q->where('is_deleted', 0));

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['month'])) {
            $query->whereHas('payrollRun', fn ($q) => $q->where('month', $filters['month']));
        }
        if (!empty($filters['year'])) {
            $query->whereHas('payrollRun', fn ($q) => $q->where('year', $filters['year']));
        }

        $query = $this->scope($query);

        return $query->get()
            ->groupBy(fn ($row) => $row->branch_id ?? 'unassigned')
            ->map(function ($group) {
                $branch = $group->first()->branch;

                return (object) [
                    'branch' => $branch?->name ?? 'Unassigned',
                    'employee_count' => $group->pluck('employee_id')->unique()->count(),
                    'total_gross' => round($group->sum('total_earnings'), 2),
                    'total_deductions' => round($group->sum('total_deductions'), 2),
                    'total_net_salary' => round($group->sum('net_salary'), 2),
                ];
            })
            ->values();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('total_gross', fn ($row) => currency($row->total_gross))
            ->addColumn('total_deductions', fn ($row) => currency($row->total_deductions))
            ->addColumn('total_net_salary', fn ($row) => currency($row->total_net_salary))
            ->make(true);
    }
}
