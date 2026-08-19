<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\Payslip;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class PayrollDisbursementReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = Payslip::with(['employee.user', 'employee.department', 'payrollRun'])
            ->whereHas('payrollRun', fn ($q) => $q->where('is_deleted', 0));

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['month'])) {
            $query->whereHas('payrollRun', fn ($q) => $q->where('month', $filters['month']));
        }
        if (!empty($filters['year'])) {
            $query->whereHas('payrollRun', fn ($q) => $q->where('year', $filters['year']));
        }
        if (!empty($filters['payment_method'])) {
            $query->whereHas('employee', fn ($q) => $q->where('payment_method', $filters['payment_method']));
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query = $this->scope($query);

        return $query->orderBy('date_created', 'desc')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = [
            'total_paid' => currency($rows->where('status', 'paid')->sum('net_salary')),
            'total_unpaid' => currency($rows->where('status', '!=', 'paid')->sum('net_salary')),
        ];

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('period', fn ($row) => $row->payrollRun ? (date('F', mktime(0, 0, 0, $row->payrollRun->month, 1)) . ' ' . $row->payrollRun->year) : '-')
            ->addColumn('net_salary', fn ($row) => currency($row->net_salary))
            ->addColumn('payment_method', fn ($row) => $row->employee?->payment_method ? ucfirst($row->employee->payment_method) : '-')
            ->addColumn('bank_account_number', fn ($row) => $row->employee?->bank_account_number ?? '-')
            ->addColumn('payment_status', fn ($row) => $row->status == 'paid' ? 'Paid' : 'Unpaid')
            ->addColumn('paid_at', fn ($row) => $row->paid_at ? localDate($row->paid_at) : '-')
            ->with($totals)
            ->make(true);
    }
}
