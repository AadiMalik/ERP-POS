<?php

namespace App\Services\Concrete\Admin;

use App\Models\Business;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class BusinessService
{
    protected $model_business;

    public function __construct()
    {
        $this->model_business =
            new Repository(new Business());
    }

    public function getData($data)
    {
        $datatable = $this->model_business->getModel()::where('is_deleted', 0);
        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {
                if ($item->status == 'active') {
                    return '
                    <span class="badge bg-label-success me-1 mb-1">
                        Active
                    </span>
                ';
                } elseif ($item->status == 'suspended') {
                    return '
                    <span class="badge bg-label-warning me-1 mb-1">
                        Suspended
                    </span>
                ';
                } else {
                    return '
                    <span class="badge bg-label-danger me-1 mb-1">
                        Expired
                    </span>
                ';
                }
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('business.edit', $item->id) . "'
                    id='editBusiness'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteBusiness'
                    data-id='{$item->id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['id'])) {
            $obj['updatedby_id'] = Auth::user()->id;
            $obj['date_updated'] = now();
            $this->model_business->update($obj, $obj['id']);
            return $this->model_business->find($obj['id']);
        }
        $obj['createdby_id'] = Auth::user()->id;
        $obj['date_created'] = now();
        return $this->model_business->create($obj);
    }

    public function edit($id)
    {
        return $this->model_business->find($id);
    }

    public function delete($id)
    {
        return $this->model_business->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $id);
    }

    public function getAll()
    {
        return Package::where('is_deleted', 0)->get();
    }
}
