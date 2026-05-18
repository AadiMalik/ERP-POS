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
use Spatie\Permission\Models\Role;

class RoleService
{
    
    protected $model_role;
    public function __construct()
    {
        // set the model
        $this->model_role = new Repository(new Role);
    }

    public function getData($data)
    {
        $datatable = $this->model_role->all();
        return $this->model_role->all();
    }
}
