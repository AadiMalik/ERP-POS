<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Enums\Filter;
use App\Enums\Status;
use App\Models\LeaveType;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class LeaveTypeService
{
    protected $model_leave_type;

    public function __construct()
    {
        $this->model_leave_type = new Repository(new LeaveType());
    }

    public function getData($obj)
    {
        $orderBy = $obj['orderBy'] ?? Filter::ORDERBY;

        $datatable = $this->model_leave_type->getModel()::where('is_deleted', 0)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('is_paid', function ($item) {
                return $item->is_paid ? '<span class="badge bg-label-success">Paid</span>' : '<span class="badge bg-label-secondary">Unpaid</span>';
            })
            ->addColumn('status', function ($item) {
                return $item->status == Status::ACTIVE
                    ? '<span class="badge bg-label-success">Active</span>'
                    : '<span class="badge bg-label-secondary">Inactive</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('leave-type.edit', $item->leave_type_id) . "'>
                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteLeaveType'
                    data-id='{$item->leave_type_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['is_paid', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['leave_type_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model_leave_type->update($obj, $obj['leave_type_id']);
            return $this->model_leave_type->find($obj['leave_type_id']);
        }

        $obj['leave_type_id'] = generateUuid();
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        return $this->model_leave_type->create($obj);
    }

    public function getById($leave_type_id)
    {
        return $this->model_leave_type->find($leave_type_id);
    }

    public function delete($leave_type_id)
    {
        return $this->model_leave_type->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $leave_type_id);
    }

    public function getAllActive()
    {
        $query = $this->model_leave_type->getModel()::where('is_deleted', 0)->where('status', Status::ACTIVE);
        return applyRoleScope($query)->orderBy('name')->get();
    }
}
