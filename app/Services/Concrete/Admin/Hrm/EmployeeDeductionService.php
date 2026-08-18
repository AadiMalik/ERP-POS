<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Enums\Filter;
use App\Enums\Status;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class EmployeeDeductionService
{
    protected $model_deduction;

    public function __construct()
    {
        $this->model_deduction = new Repository(new EmployeeDeduction());
    }

    public function getData($obj)
    {
        $wh = [];
        if (isset($obj['employee_id']) && $obj['employee_id'] != "") {
            $wh[] = ['employee_id', $obj['employee_id']];
        }

        $datatable = $this->model_deduction->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->with(['employee.user'])
            ->orderBy('date_created', 'desc');
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('employee', function ($item) {
                return $item->employee?->user?->name ?? '-';
            })
            ->addColumn('is_recurring', function ($item) {
                return $item->is_recurring ? 'Recurring' : 'One-time';
            })
            ->addColumn('status', function ($item) {
                return $item->status == Status::ACTIVE
                    ? '<span class="badge bg-label-success">Active</span>'
                    : '<span class="badge bg-label-secondary">Inactive</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('employee-deduction.edit', $item->employee_deduction_id) . "'>
                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteEmployeeDeduction'
                    data-id='{$item->employee_deduction_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['is_recurring', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        $employee = Employee::findOrFail($obj['employee_id']);
        $obj['business_id'] = $employee->business_id;
        $obj['is_recurring'] = $obj['is_recurring'] ?? 0;

        if (!empty($obj['employee_deduction_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model_deduction->update($obj, $obj['employee_deduction_id']);
            return $this->model_deduction->find($obj['employee_deduction_id']);
        }

        $obj['employee_deduction_id'] = generateUuid();
        $obj['status'] = 'active';
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        return $this->model_deduction->create($obj);
    }

    public function getById($employee_deduction_id)
    {
        return $this->model_deduction->find($employee_deduction_id);
    }

    public function delete($employee_deduction_id)
    {
        return $this->model_deduction->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $employee_deduction_id);
    }

    /**
     * Deductions applicable to one employee within a given month (active,
     * effective in range) - consumed by PayrollService::generate().
     */
    public function activeForMonth($employee_id, $year, $month)
    {
        $month_start = sprintf('%04d-%02d-01', $year, $month);
        $month_end = date('Y-m-t', strtotime($month_start));

        return $this->model_deduction->getModel()::where('employee_id', $employee_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->where('effective_from', '<=', $month_end)
            ->where(function ($q) use ($month_start) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $month_start);
            })
            ->get();
    }
}
