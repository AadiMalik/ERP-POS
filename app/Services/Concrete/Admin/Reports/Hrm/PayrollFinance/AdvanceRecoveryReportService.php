<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\EmployeeAdvance;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Recovered = amount - remaining_balance for advances that were approved
 * (status repaying/completed) - mirrors EmployeeAdvanceService::deductInstallment(),
 * the single place remaining_balance is decremented as payroll recovers
 * installments.
 */
class AdvanceRecoveryReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = EmployeeAdvance::with(['employee.user', 'employee.department'])
            ->where('is_deleted', 0)
            ->whereIn('status', ['repaying', 'completed']);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }

        $query = $this->scope($query);

        return $query->orderBy('date_created', 'desc')->get()->map(function ($advance) {
            $advance->recovered = round($advance->amount - $advance->remaining_balance, 2);
            $advance->next_installment = $advance->remaining_balance > 0
                ? min($advance->installment_amount, $advance->remaining_balance)
                : 0;

            return $advance;
        });
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = [
            'total_recovered' => currency($rows->sum('recovered')),
            'total_outstanding' => currency($rows->sum('remaining_balance')),
        ];

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('amount', fn ($row) => currency($row->amount))
            ->addColumn('recovered', fn ($row) => currency($row->recovered))
            ->addColumn('remaining_balance', fn ($row) => currency($row->remaining_balance))
            ->addColumn('next_installment', fn ($row) => currency($row->next_installment))
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->with($totals)
            ->make(true);
    }
}
