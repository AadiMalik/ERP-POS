<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Branch;
use App\Models\Package;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class ProductService
{
    protected $model_branch;

    public function __construct()
    {
        $this->model_branch = new Repository(new Branch());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }

        if (!empty($obj['end_date'])) {
            $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }
        $with = ['business'];
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];
        $datatable = $this->model_branch->getModel()::where($wh)
            ->with($with)
            ->orderBy('name', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {

                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusBranch"
                        type="checkbox"
                        data-id="' . $item->branch_id . '"
                        ' . $checked . '>
                </div>
            ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('branch.edit', $item->branch_id) . "'
                    id='editBranch'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteBranch'
                    data-id='{$item->branch_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {

        if (!empty($obj['branch_id'])) {
            $obj['updatedby_id'] = Auth::user()->id;
            $obj['date_updated'] = now();
            $this->model_branch->update($obj, $obj['branch_id']);
            return $this->model_branch->find($obj['branch_id']);
        }
        //check limit
        $limit = checkPackageLimit('branches');

        if (!$limit['status']) {
            throw new Exception($limit['message']);
        }

        $obj['branch_id'] = generateUuid();
        $obj['createdby_id'] = Auth::user()->id;
        $obj['date_created'] = now();
        $saved_obj = $this->model_branch->create($obj);
        return $saved_obj;
    }

    public function getById($branch_id)
    {
        return $this->model_branch->find($branch_id);
    }
    public function status($branch_id)
    {
        return $this->model_branch->update([
            'status' => ($this->model_branch->find($branch_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $branch_id);
    }

    public function delete($branch_id)
    {
        return $this->model_branch->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $branch_id);
    }

    public function getAll()
    {
        return $this->model_branch->getModel()::with('business')
            ->where('business_id', Auth::user()->business_id)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getAllActive()
    {
        return $this->model_branch->getModel()::with('business')
            ->where('business_id', Auth::user()->business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByBusiness($business_id)
    {
        return $this->model_branch->getModel()::with('business')
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByBrand($brand_id)
    {
        return $this->model_branch->getModel()::with('business')
            ->where('brand_id', $brand_id)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByCategory($category_id)
    {
        return $this->model_branch->getModel()::with('business')
            ->where('category_id', $category_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
