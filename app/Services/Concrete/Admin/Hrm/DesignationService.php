<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Enums\Filter;
use App\Enums\Status;
use App\Models\Designation;
use App\Repository\Repository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class DesignationService
{
    protected $model_designation;

    public function __construct()
    {
        $this->model_designation = new Repository(new Designation());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['department_id']) && $obj['department_id'] != 0 && $obj['department_id'] != "") {
            $wh[] = ['department_id', $obj['department_id']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $datatable = $this->model_designation->getModel()::where($wh)
            ->with(['department'])
            ->where('is_deleted', 0)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('department', function ($item) {
                return $item->department?->name ?? '-';
            })
            ->addColumn('status', function ($item) {
                return $item->status == Status::ACTIVE
                    ? '<span class="badge bg-label-success">Active</span>'
                    : '<span class="badge bg-label-secondary">Inactive</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('designation.edit', $item->designation_id) . "'>
                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteDesignation'
                    data-id='{$item->designation_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['department', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['designation_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model_designation->update($obj, $obj['designation_id']);
            return $this->model_designation->find($obj['designation_id']);
        }

        $obj['designation_id'] = generateUuid();
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        return $this->model_designation->create($obj);
    }

    public function getById($designation_id)
    {
        return $this->model_designation->find($designation_id);
    }

    public function delete($designation_id)
    {
        return $this->model_designation->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $designation_id);
    }

    public function getAllActive()
    {
        $query = $this->model_designation->getModel()::where('is_deleted', 0)
            ->where('status', Status::ACTIVE);
        return applyRoleScope($query)->orderBy('name')->get();
    }
}
