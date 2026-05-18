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
                $edit_column    = "<a class='text-success mr-2' id='editPermission' href='javascript:void(0)' data-toggle='tooltip'  data-id='" . $item->id . "' data-original-title='Edit'><i title='Edit' class='nav-icon mr-2 i-Pen-2'></i>Edit</a>";
                $delete_column    = "<a class='text-danger mr-2'  id='deletePermission' href='javascript:void(0)' data-toggle='tooltip'  data-id='" . $item->id . "' data-original-title='delete'><i title='Delete' class='nav-icon mr-2 fa fa-trash'></i>Delete</a>";


                // if (isset($menu_permission) && $menu_permission['can_edit'] == 1) {
                    $action_column .= $edit_column;
                // }
                // if (isset($menu_permission) && $menu_permission['can_delete'] == 1) {
                    $action_column .= $delete_column;
                // }


                return $action_column;
            })
            ->rawColumns(['is_system_only','action'])
            ->make(true);
        return $data;
    }
}
