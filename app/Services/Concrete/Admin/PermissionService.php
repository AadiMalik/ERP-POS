<?php

namespace App\Services\Concrete\Admin;

use App\Repository\Repository;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\DataTables;
use App\Enums\CUDOperations;
use App\Enums\RoleNames;
use Spatie\Permission\Models\Permission;

class PermissionService
{

    protected $model_permission;
    public function __construct()
    {
        // set the model
        $this->model_permission = new Repository(new Permission());
    }

    public function getData($data)
    {
        
        $datatable = $this->model_permission->getModel()::query();

        $data = DataTables::of($datatable)
            ->addColumn('is_system_only', function ($item) {
                if ($item->is_system_only == 0) {
                    return 'NO';
                } else {
                    return 'YES';
                }
            })
            ->addColumn('action', function ($item) {
                $action_column = '';
                $edit_column    = "<a class='btn btn-icon btn-outline-primary mr-2' id='editPermission' href='javascript:void(0)' data-toggle='tooltip'  data-id='" . $item->id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>";
                $delete_column    = "<a class='btn btn-icon btn-outline-danger mr-2'  id='deletePermission' href='javascript:void(0)' data-toggle='tooltip'  data-id='" . $item->id . "' data-original-title='delete'><i title='Delete' class='icon-base fa fa-trash'></i></a>";


                // if (isset($menu_permission) && $menu_permission['can_edit'] == 1) {
                $action_column .= $edit_column;
                // }
                // if (isset($menu_permission) && $menu_permission['can_delete'] == 1) {
                $action_column .= $delete_column;
                // }


                return $action_column;
            })
            ->rawColumns(['is_system_only', 'action'])
            ->make(true);
        return $data;
    }

    public function save($obj)
    {
        if (isset($obj['id']) && $obj['id'] > 0) {
            $this->model_permission->update($obj, $obj['id']);
            $saved_obj = $this->model_permission->find($obj['id']);
        } else {
            $saved_obj = $this->model_permission->create($obj);
        }

        if (!$saved_obj)
            return false;

        return $saved_obj;
    }

    public function edit($id)
    {
        return $this->model_permission->find($id);
    }

    public function delete($id)
    {
        return $this->model_permission->delete($id);
    }

    // get all
    public function getAll()
    {
        $permissions = $this->model_permission->all();
        if (getRoleName() == RoleNames::BUSINESSADMIN) {
            $permissions = $permissions->where('is_system_only', 0);
        }
        return $permissions;
    }
}
