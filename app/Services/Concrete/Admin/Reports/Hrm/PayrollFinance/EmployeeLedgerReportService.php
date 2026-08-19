<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\EmployeeLedgerEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class EmployeeLedgerReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = EmployeeLedgerEntry::with(['employee.user', 'employee.department']);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('entry_date', '>=', Carbon::parse($filters['start_date'])->toDateString());
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('entry_date', '<=', Carbon::parse($filters['end_date'])->toDateString());
        }

        $query = $this->scope($query);

        return $query->orderBy('entry_date', 'desc')->orderBy('date_created', 'desc')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = [
            'total_debit' => currency($rows->sum('debit')),
            'total_credit' => currency($rows->sum('credit')),
        ];

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('entry_date', fn ($row) => localDate($row->entry_date))
            ->addColumn('type', fn ($row) => ucfirst($row->type))
            ->addColumn('debit', fn ($row) => $row->debit > 0 ? currency($row->debit) : '')
            ->addColumn('credit', fn ($row) => $row->credit > 0 ? currency($row->credit) : '')
            ->addColumn('balance_after', fn ($row) => currency($row->balance_after))
            ->with($totals)
            ->make(true);
    }
}
