<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Models\Branch;
use App\Models\Package;
use App\Repository\Repository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class BranchService
{
    protected $model_branch;
    protected $model_package;
    protected $model_branch_subscription;

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
        if (isset($obj['start_date']) && $obj['start_date'] != 0 && $obj['start_date'] != "") {
            $wh[] = ['date_created', '>=', $obj['start_date']];
        }
        if (isset($obj['end_date']) && $obj['end_date'] != 0 && $obj['end_date'] != "") {
            $wh[] = ['date_created', '<=', $obj['end_date']];
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
                if ($item->status == 'active') {
                    return '
                    <span class="badge bg-label-success me-1 mb-1">
                        Active
                    </span>
                ';
                } else {
                    return '
                    <span class="badge bg-label-danger me-1 mb-1">
                        Inactive
                    </span>
                ';
                }
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
            ->get();
    }

    public function getByBusiness($business_id)
    {
        return $this->model_branch->getModel()::with('business')
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
