<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\EmployeeDeduction;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class DeductionReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = EmployeeDeduction::with(['employee.user', 'employee.department'])
            ->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }
        if (isset($filters['is_recurring']) && $filters['is_recurring'] !== '') {
            $query->where('is_recurring', $filters['is_recurring']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query = $this->scope($query);

        return $query->orderBy('effective_from', 'desc')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = ['total_amount' => currency($rows->sum('amount'))];

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('amount', fn ($row) => currency($row->amount))
            ->addColumn('is_recurring', fn ($row) => $row->is_recurring ? 'Yes' : 'No')
            ->addColumn('effective_from', fn ($row) => localDate($row->effective_from))
            ->addColumn('effective_to', fn ($row) => $row->effective_to ? localDate($row->effective_to) : '-')
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->with($totals)
            ->make(true);
    }
}
