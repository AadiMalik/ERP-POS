<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\EmployeeSalaryStructure;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class SalaryStructureReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = EmployeeSalaryStructure::with(['employee.user', 'employee.department', 'items.component'])
            ->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', 'active');
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }

        $query = $this->scope($query);

        return $query->get()->map(function ($structure) {
            $structure->components_summary = $structure->items->map(fn ($item) => $item->component?->name . ' (' . $item->amount_or_percentage . ')')->implode(', ');

            return $structure;
        });
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('effective_from', fn ($row) => localDate($row->effective_from))
            ->addColumn('basic_salary', fn ($row) => currency($row->basic_salary))
            ->addColumn('overtime_rate_per_hour', fn ($row) => currency($row->overtime_rate_per_hour))
            ->addColumn('components_summary', fn ($row) => $row->components_summary)
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->make(true);
    }
}
