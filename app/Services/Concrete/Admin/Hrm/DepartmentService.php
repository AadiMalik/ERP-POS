<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Department;
use App\Repository\Repository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class DepartmentService
{
    protected $model_department;

    public function __construct()
    {
        $this->model_department = new Repository(new Department());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['branch_id']) && $obj['branch_id'] != 0 && $obj['branch_id'] != "") {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $datatable = $this->model_department->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {
                return $item->status == Status::ACTIVE
                    ? '<span class="badge bg-label-success">Active</span>'
                    : '<span class="badge bg-label-secondary">Inactive</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('department.edit', $item->department_id) . "'>
                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteDepartment'
                    data-id='{$item->department_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['department_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model_department->update($obj, $obj['department_id']);
            return $this->model_department->find($obj['department_id']);
        }

        $obj['department_id'] = generateUuid();
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        return $this->model_department->create($obj);
    }

    public function getById($department_id)
    {
        return $this->model_department->find($department_id);
    }

    public function delete($department_id)
    {
        return $this->model_department->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $department_id);
    }

    public function getAllActive()
    {
        $query = $this->model_department->getModel()::where('is_deleted', 0)
            ->where('status', Status::ACTIVE);
        return applyRoleScope($query)->orderBy('name')->get();
    }
}
