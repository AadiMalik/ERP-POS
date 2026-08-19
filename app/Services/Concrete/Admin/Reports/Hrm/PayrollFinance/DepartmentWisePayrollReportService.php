<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\Payslip;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class DepartmentWisePayrollReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = Payslip::with(['employee.department', 'payrollRun'])
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

        return $query->get()
            ->groupBy(fn ($row) => $row->employee?->department?->department_id ?? 'unassigned')
            ->map(function ($group) {
                $department = $group->first()->employee?->department;

                return (object) [
                    'department' => $department?->name ?? 'Unassigned',
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
