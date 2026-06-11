<?php

namespace App\Services\Concrete\Admin;

use App\Models\Package;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class PackageService
{
    protected $model_package;

    public function __construct()
    {
        $this->model_package =
            new Repository(new Package());
    }

    public function getData($data)
    {
        $datatable = $this->model_package->getModel()::where('is_deleted', 0);
        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {
                return $item->status
                    ? '
                    <span class="badge bg-label-success me-1 mb-1">
                        Active
                    </span>
                '
                    : '
                    <span class="badge bg-label-danger me-1 mb-1">
                        Inactive
                    </span>
                ';
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('packages.edit', $item->package_id) . "'
                    id='editPackage'>
                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-warning mr-2'
                     id='viewPackage' href='javascript:void(0)'
                    data-id='{$item->package_id}'>
                    <i class='fa fa-eye'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deletePackage' href='javascript:void(0)'
                    data-id='{$item->package_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status','action'])
            ->make(true);
    }

    public function save($obj)
    {
        if (!empty($obj['package_id'])) {
            $obj['updatedby_id'] = Auth::user()->id;
            $obj['date_updated'] = now();
            $this->model_package->update($obj, $obj['package_id']);
            return $this->model_package->find($obj['package_id']);
        }
        $obj['package_id'] = generateUuid();
        $obj['createdby_id'] = Auth::user()->id;
        $obj['date_created'] = now();
        return $this->model_package->create($obj);
    }

    public function getById($package_id)
    {
        return $this->model_package->find($package_id);
    }

    public function delete($package_id)
    {
        return $this->model_package->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $package_id);
    }

    public function getAll()
    {
        return $this->model_package->getModel()::where('is_deleted', 0)->get();
    }
}
