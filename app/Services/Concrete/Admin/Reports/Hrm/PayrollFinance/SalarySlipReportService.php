<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\Payslip;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class SalarySlipReportService extends BasePayrollFinanceReportService
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

        return $query->orderBy('date_created', 'desc')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('period', fn ($row) => $row->payrollRun ? (date('F', mktime(0, 0, 0, $row->payrollRun->month, 1)) . ' ' . $row->payrollRun->year) : '-')
            ->addColumn('net_salary', fn ($row) => currency($row->net_salary))
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->addColumn('paid_at', fn ($row) => $row->paid_at ? localDate($row->paid_at) : '-')
            ->addColumn('action', fn ($row) => "<a class='btn btn-icon btn-outline-primary' href='" . route('payslip.pdf', $row->payslip_id) . "' target='_blank'><i class='fa fa-file-pdf'></i></a>")
            ->rawColumns(['action'])
            ->make(true);
    }
}
