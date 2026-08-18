<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Enums\Filter;
use App\Enums\Status;
use App\Models\SalaryComponent;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class SalaryComponentService
{
    protected $model_salary_component;

    public function __construct()
    {
        $this->model_salary_component = new Repository(new SalaryComponent());
    }

    public function getData($obj)
    {
        $orderBy = $obj['orderBy'] ?? Filter::ORDERBY;

        $datatable = $this->model_salary_component->getModel()::where('is_deleted', 0)->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('type', function ($item) {
                return $item->type == 'earning'
                    ? '<span class="badge bg-label-success">Earning</span>'
                    : '<span class="badge bg-label-danger">Deduction</span>';
            })
            ->addColumn('calculation_type', function ($item) {
                return $item->calculation_type == 'fixed' ? 'Fixed Amount' : '% of Basic';
            })
            ->addColumn('status', function ($item) {
                return $item->status == Status::ACTIVE
                    ? '<span class="badge bg-label-success">Active</span>'
                    : '<span class="badge bg-label-secondary">Inactive</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('salary-component.edit', $item->salary_component_id) . "'>
                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteSalaryComponent'
                    data-id='{$item->salary_component_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['type', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['salary_component_id'])) {
            $obj['updatedby_id'] = Auth::id();
            $obj['date_updated'] = now();
            $this->model_salary_component->update($obj, $obj['salary_component_id']);
            return $this->model_salary_component->find($obj['salary_component_id']);
        }

        $obj['salary_component_id'] = generateUuid();
        $obj['createdby_id'] = Auth::id();
        $obj['date_created'] = now();
        return $this->model_salary_component->create($obj);
    }

    public function getById($salary_component_id)
    {
        return $this->model_salary_component->find($salary_component_id);
    }

    public function delete($salary_component_id)
    {
        return $this->model_salary_component->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $salary_component_id);
    }

    public function getAllActive($type = null)
    {
        $query = $this->model_salary_component->getModel()::where('is_deleted', 0)->where('status', Status::ACTIVE);
        if ($type) {
            $query->where('type', $type);
        }
        return applyRoleScope($query)->orderBy('name')->get();
    }
}
