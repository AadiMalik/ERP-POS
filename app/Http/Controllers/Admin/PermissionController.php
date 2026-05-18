<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    protected $permission_service;
    public function __construct(PermissionService  $permission_service)
    {
        $this->permission_service = $permission_service;
    }
    public function index()
    {
        return view('admin.permissions.index');
    }

    public function getData(Request $request)
    {
        return $this->permission_service->getData($request->all());
    }
}
