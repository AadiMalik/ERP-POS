<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Models\Employee;
use App\Models\EmployeeLedgerEntry;
use App\Enums\Filter;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Single writer for the employee running ledger - every module that affects
 * an employee's balance (advances, payroll deductions, manual adjustments)
 * calls addEntry() here instead of writing employee_ledger_entries directly,
 * so balance_after is always computed from the true previous row.
 *
 * Convention: debit increases what the employee owes the company (e.g. an
 * advance disbursed), credit decreases it (e.g. an installment recovered via
 * payroll). Salary/payment entries are recorded for visibility with
 * debit=credit=0 so they never distort the advance-recovery balance.
 */
class EmployeeLedgerService
{
    public function addEntry($employee_id, $type, $debit, $credit, $description, $referenceType = null, $referenceId = null, $business_id = null)
    {
        $last = EmployeeLedgerEntry::where('employee_id', $employee_id)
            ->orderBy('date_created', 'desc')
            ->first();
        $previous_balance = $last->balance_after ?? 0;
        $balance_after = round($previous_balance + $debit - $credit, 2);

        $employee = $business_id ? null : Employee::find($employee_id);

        return EmployeeLedgerEntry::create([
            'employee_ledger_entry_id' => generateUuid(),
            'employee_id' => $employee_id,
            'entry_date' => now()->toDateString(),
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'debit' => $debit,
            'credit' => $credit,
            'balance_after' => $balance_after,
            'description' => $description,
            'business_id' => $business_id ?? $employee?->business_id,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);
    }

    public function getData($obj)
    {
        $wh = [];
        if (isset($obj['employee_id']) && $obj['employee_id'] != "") {
            $wh[] = ['employee_id', $obj['employee_id']];
        }

        $datatable = EmployeeLedgerEntry::where($wh)
            ->with(['employee.user'])
            ->orderBy('date_created', $obj['orderBy'] ?? Filter::ORDERBY);
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('employee', function ($item) {
                return $item->employee?->user?->name ?? '-';
            })
            ->addColumn('type', function ($item) {
                return ucfirst($item->type);
            })
            ->rawColumns([])
            ->make(true);
    }

    public function getBalance($employee_id)
    {
        $last = EmployeeLedgerEntry::where('employee_id', $employee_id)
            ->orderBy('date_created', 'desc')
            ->first();
        return $last->balance_after ?? 0;
    }

    public function getByEmployee($employee_id)
    {
        return EmployeeLedgerEntry::where('employee_id', $employee_id)
            ->orderBy('date_created', 'asc')
            ->get();
    }
}
