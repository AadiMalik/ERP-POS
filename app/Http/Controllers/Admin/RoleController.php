<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\PermissionService;
use App\Services\Concrete\Admin\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected $role_service;
    protected $permission_service;
    public function __construct(RoleService  $role_service, PermissionService $permission_service)
    {
        $this->role_service = $role_service;
        $this->permission_service = $permission_service;
    }
    public function index()
    {
        return view('admin.roles.index');
    }

    public function getData(Request $request)
    {
        return $this->role_service->getData($request->all());
    }

    public function create()
    {
        $permissions = $this->permission_service->getAll();
        return view('admin.roles.create', compact('permissions'));
    }

    public function edit($id)
    {
        $role = $this->role_service->getByid($id);
        $permissions = $this->permission_service->getAll();
        return view('admin.roles.create', compact('role', 'permissions'));
    }
}
