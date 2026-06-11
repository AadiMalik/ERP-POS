<?php

namespace App\Services\Concrete\Admin;

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

    public function getData($data)
    {
        $datatable = $this->model_branch->getModel()::where('is_deleted', 0);
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
}
