<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\PermissionService;
use App\Services\Concrete\Admin\RoleService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    use ResponseAPI;
    protected $role_service;
    protected $permission_service;
    protected $business_service;
    public function __construct(
        RoleService  $role_service,
        PermissionService $permission_service,
        BusinessService $business_service
    ) {
        $this->role_service = $role_service;
        $this->permission_service = $permission_service;
        $this->business_service = $business_service;
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
        $business = $this->business_service->getAll();
        return view('admin.roles.create', compact('permissions','business'));
    }

    public function store(Request $request)
    {

        $rules = [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('roles')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id);
                    })
                    ->ignore($request->id),
            ],
            'description' => 'required',
        ];
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }
        try {
            $obj = [
                'id' => $request->id,
                'name' => $request->name,
                'business_id' => $request->business_id ?? Auth::user()->business_id,
            ];

            $role = $this->role_service->save($obj);
            $role->syncPermissions($request->permissions);
            return redirect('admin/roles')->with('success', Message::SAVE);
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $role = $this->role_service->getByid($id);
        $permissions = $this->permission_service->getAll();
        return view('admin.roles.create', compact('role', 'permissions'));
    }

    public function reset()
    {
        try {

            $roles = $this->role_service->resetBusinessRoles();
            return $this->success(
                Message::SUCCESS,
                $roles
            );
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
