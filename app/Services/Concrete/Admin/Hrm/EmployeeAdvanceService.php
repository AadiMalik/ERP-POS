<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Enums\Filter;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Repository\Repository;
use App\Traits\Auditable;
use App\Traits\Notifiable;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class EmployeeAdvanceService
{
    use Auditable, Notifiable;

    protected $model_advance;
    protected $ledger_service;

    public function __construct(EmployeeLedgerService $ledger_service)
    {
        $this->model_advance = new Repository(new EmployeeAdvance());
        $this->ledger_service = $ledger_service;
    }

    public function getData($obj)
    {
        $wh = [];
        if (isset($obj['employee_id']) && $obj['employee_id'] != "") {
            $wh[] = ['employee_id', $obj['employee_id']];
        }
        if (isset($obj['status']) && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }

        $datatable = $this->model_advance->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->with(['employee.user'])
            ->orderBy('date_created', 'desc');
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('employee', function ($item) {
                return $item->employee?->user?->name ?? '-';
            })
            ->addColumn('status', function ($item) {
                $map = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'repaying' => 'info', 'completed' => 'secondary'];
                $color = $map[$item->status] ?? 'secondary';
                return '<span class="badge bg-label-' . $color . '">' . ucfirst($item->status) . '</span>';
            })
            ->addColumn('action', function ($item) {
                $actions = '';
                if ($item->status == 'pending') {
                    $actions .= "<a class='btn btn-icon btn-outline-success mr-2' id='approveAdvance' data-id='{$item->employee_advance_id}'><i class='fa fa-check'></i></a>";
                    $actions .= "<a class='btn btn-icon btn-outline-danger mr-2' id='rejectAdvance' data-id='{$item->employee_advance_id}'><i class='fa fa-times'></i></a>";
                }
                $actions .= "<a class='btn btn-icon btn-outline-secondary' id='deleteAdvance' data-id='{$item->employee_advance_id}'><i class='fa fa-trash'></i></a>";
                return $actions;
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function request($obj)
    {
        $employee = Employee::findOrFail($obj['employee_id']);

        $data = [
            'employee_advance_id' => generateUuid(),
            'employee_id' => $obj['employee_id'],
            'amount' => $obj['amount'],
            'reason' => $obj['reason'] ?? null,
            'request_date' => now()->toDateString(),
            'status' => 'pending',
            'installments_count' => $obj['installments_count'] ?? 1,
            'business_id' => $employee->business_id,
            'branch_id' => $employee->branch_id,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ];

        $advance = $this->model_advance->create($data);

        $this->logActivity('employee-advance', $advance->employee_advance_id, 'requested', null, $data, null, $employee->business_id, $employee->branch_id);
        $this->notify('employee_advance', $employee->business_id, $employee->branch_id, 'New Advance Request', ($employee->user->name ?? 'An employee') . ' requested a salary advance.', 'employee-advance', $advance->employee_advance_id, route('employee-advance.index'));

        return $advance;
    }

    public function getById($employee_advance_id)
    {
        return $this->model_advance->getModel()::with(['employee.user'])->findOrFail($employee_advance_id);
    }

    public function decide($employee_advance_id, $status, $installments_count = 1)
    {
        $advance = $this->getById($employee_advance_id);
        if ($advance->status != 'pending') {
            throw new Exception('This advance request has already been decided.');
        }

        if ($status == 'approved') {
            $installments_count = max(1, (int) $installments_count);
            $installment_amount = round($advance->amount / $installments_count, 2);

            $this->model_advance->update([
                'status' => 'repaying',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'installments_count' => $installments_count,
                'installment_amount' => $installment_amount,
                'remaining_balance' => $advance->amount,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ], $employee_advance_id);

            $this->ledger_service->addEntry(
                $advance->employee_id,
                'advance',
                $advance->amount,
                0,
                'Advance approved: ' . ($advance->reason ?? ''),
                'employee-advance',
                $employee_advance_id,
                $advance->business_id
            );
        } elseif ($status == 'rejected') {
            $this->model_advance->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ], $employee_advance_id);
        } else {
            throw new Exception('Invalid decision.');
        }

        $this->logActivity('employee-advance', $employee_advance_id, $status, ['status' => 'pending'], ['status' => $status]);
    }

    public function delete($employee_advance_id)
    {
        $advance = $this->getById($employee_advance_id);
        if (!in_array($advance->status, ['pending', 'rejected'], true)) {
            throw new Exception('Only pending or rejected advances can be deleted.');
        }

        return $this->model_advance->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $employee_advance_id);
    }

    /**
     * Advances currently being recovered - consumed by PayrollService, which
     * deducts min(installment_amount, remaining_balance) per run via
     * deductInstallment().
     */
    public function getActiveForEmployee($employee_id)
    {
        return $this->model_advance->getModel()::where('employee_id', $employee_id)
            ->where('status', 'repaying')
            ->where('remaining_balance', '>', 0)
            ->where('is_deleted', 0)
            ->get();
    }

    public function deductInstallment(EmployeeAdvance $advance, $amount, $payslip_id = null)
    {
        $new_balance = round($advance->remaining_balance - $amount, 2);
        $status = $new_balance <= 0 ? 'completed' : 'repaying';

        $this->model_advance->update([
            'remaining_balance' => max(0, $new_balance),
            'status' => $status,
            'date_updated' => now(),
        ], $advance->employee_advance_id);

        $this->ledger_service->addEntry(
            $advance->employee_id,
            'deduction',
            0,
            $amount,
            'Advance installment recovered via payroll',
            'payslip',
            $payslip_id,
            $advance->business_id
        );
    }
}
