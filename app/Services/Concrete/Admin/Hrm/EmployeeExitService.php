<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Enums\EmployeeStatus;
use App\Enums\Status;
use App\Models\Employee;
use App\Models\EmployeeExit;
use App\Models\ExitClearance;
use App\Models\User;
use App\Traits\Auditable;
use App\Traits\Notifiable;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class EmployeeExitService
{
    use Auditable, Notifiable;

    /**
     * Fixed clearance checklist - kept as a constant list rather than a new
     * master-data CRUD to keep this practical; still fully permission-gated
     * per clearance action.
     */
    const CLEARANCE_AREAS = ['IT', 'Finance', 'Assets', 'HR/Admin', 'Department'];

    protected $ledger_service;

    public function __construct(EmployeeLedgerService $ledger_service)
    {
        $this->ledger_service = $ledger_service;
    }

    public function getData($obj)
    {
        $wh = [];
        if (isset($obj['status']) && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }

        $datatable = EmployeeExit::where($wh)
            ->where('is_deleted', 0)
            ->with(['employee.user'])
            ->orderBy('date_created', 'desc');
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('employee', function ($item) {
                return $item->employee?->user?->name ?? '-';
            })
            ->addColumn('status', function ($item) {
                $map = ['pending' => 'warning', 'approved' => 'info', 'rejected' => 'danger', 'finalized' => 'success'];
                return '<span class="badge bg-label-' . ($map[$item->status] ?? 'secondary') . '">' . ucfirst($item->status) . '</span>';
            })
            ->addColumn('action', function ($item) {
                return "<a class='btn btn-icon btn-outline-primary' href='" . route('employee-exit.show', $item->employee_exit_id) . "'><i class='fa fa-eye'></i></a>";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function request($obj)
    {
        $employee = Employee::findOrFail($obj['employee_id']);

        $data = [
            'employee_exit_id' => generateUuid(),
            'employee_id' => $obj['employee_id'],
            'type' => $obj['type'],
            'request_date' => now()->toDateString(),
            'notice_period_days' => $obj['notice_period_days'] ?? 0,
            'last_working_date' => $obj['last_working_date'] ?? null,
            'reason' => $obj['reason'] ?? null,
            'status' => 'pending',
            'business_id' => $employee->business_id,
            'branch_id' => $employee->branch_id,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ];

        $exit = EmployeeExit::create($data);

        $this->logActivity('employee-exit', $exit->employee_exit_id, 'requested', null, $data, null, $employee->business_id, $employee->branch_id);
        $this->notify('employee_exit', $employee->business_id, $employee->branch_id, ucfirst($obj['type']) . ' Request', ($employee->user->name ?? 'An employee') . ' submitted a ' . $obj['type'] . ' request.', 'employee-exit', $exit->employee_exit_id, route('employee-exit.show', $exit->employee_exit_id));

        return $exit;
    }

    public function getById($employee_exit_id)
    {
        return EmployeeExit::with(['employee.user', 'clearances.clearedBy'])->findOrFail($employee_exit_id);
    }

    public function decide($employee_exit_id, $status)
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new Exception('Invalid decision.');
        }

        $exit = $this->getById($employee_exit_id);
        if ($exit->status != 'pending') {
            throw new Exception('This request has already been decided.');
        }

        DB::beginTransaction();
        try {
            $exit->update([
                'status' => $status,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            if ($status == 'approved') {
                foreach (self::CLEARANCE_AREAS as $area) {
                    ExitClearance::create([
                        'exit_clearance_id' => generateUuid(),
                        'employee_exit_id' => $employee_exit_id,
                        'area' => $area,
                        'status' => 'pending',
                        'date_created' => now(),
                    ]);
                }

                Employee::where('employee_id', $exit->employee_id)->update(['status' => EmployeeStatus::ON_LEAVE]);
            }

            $this->logActivity('employee-exit', $employee_exit_id, $status, ['status' => 'pending'], ['status' => $status]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function clear($exit_clearance_id, $status, $remarks = null)
    {
        if (!in_array($status, ['cleared', 'rejected'], true)) {
            throw new Exception('Invalid clearance decision.');
        }

        $clearance = ExitClearance::findOrFail($exit_clearance_id);
        $clearance->update([
            'status' => $status,
            'cleared_by' => Auth::id(),
            'cleared_at' => now(),
            'remarks' => $remarks,
        ]);

        $this->logActivity('employee-clearance', $exit_clearance_id, $status, null, ['area' => $clearance->area, 'status' => $status]);
    }

    public function finalize($employee_exit_id, $final_settlement_amount = null)
    {
        $exit = $this->getById($employee_exit_id);

        if ($exit->status != 'approved') {
            throw new Exception('Only approved exit requests can be finalized.');
        }

        $pending_areas = $exit->clearances->where('status', 'pending')->pluck('area');
        if ($pending_areas->count() > 0) {
            throw new Exception('Pending clearance for: ' . $pending_areas->implode(', '));
        }

        $rejected_areas = $exit->clearances->where('status', 'rejected')->pluck('area');
        if ($rejected_areas->count() > 0) {
            throw new Exception('Clearance rejected for: ' . $rejected_areas->implode(', ') . '. Resolve before finalizing.');
        }

        DB::beginTransaction();
        try {
            $employee = Employee::findOrFail($exit->employee_id);
            $new_status = $exit->type == 'termination' ? EmployeeStatus::TERMINATED : EmployeeStatus::RESIGNED;

            $employee->update([
                'status' => $new_status,
                'exit_date' => $exit->last_working_date ?? now()->toDateString(),
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            User::where('id', $employee->user_id)->update(['status' => Status::INACTIVE]);

            $exit->update([
                'status' => 'finalized',
                'final_settlement_amount' => $final_settlement_amount,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            if (!empty($final_settlement_amount) && $final_settlement_amount != 0) {
                $this->ledger_service->addEntry(
                    $employee->employee_id,
                    'adjustment',
                    0,
                    $final_settlement_amount,
                    'Final settlement on ' . $exit->type,
                    'employee-exit',
                    $employee_exit_id,
                    $employee->business_id
                );
            }

            $this->logActivity('employee-exit', $employee_exit_id, 'finalized', null, ['status' => $new_status], null, $employee->business_id, $employee->branch_id);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
