<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\PermissionService;
use App\Services\Concrete\Admin\RoleService;
use App\Support\Permissions\PermissionRegistry;
use App\Support\Subscription\SubscriptionModuleRegistry;
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
        $this->middleware('permission:role.view')->only(['index', 'getData', 'byBusiness']);
        $this->middleware('permission:role.create')->only(['create']);
        $this->middleware('permission:role.edit')->only(['edit']);
        $this->middleware('permission:role.create|role.edit')->only(['store']);
        $this->middleware('permission:role.reset')->only(['reset']);

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
        $isBusinessAdmin = getRoleName() == RoleNames::BUSINESSADMIN;
        $enabledModuleKeys = $isBusinessAdmin
            ? SubscriptionModuleRegistry::enabledPermissionModuleKeysFor(Auth::user()->business)
            : null;
        $groupedPermissions = PermissionRegistry::grouped($isBusinessAdmin, $enabledModuleKeys);
        $business = $this->business_service->getAll();
        return view('admin.roles.create', compact('groupedPermissions', 'business'));
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

            $permissions = $request->permissions ?? [];

            if (getRoleName() == RoleNames::BUSINESSADMIN) {
                $enabledModuleKeys = SubscriptionModuleRegistry::enabledPermissionModuleKeysFor(Auth::user()->business);
                $permissions = array_intersect($permissions, PermissionRegistry::namesForModules($enabledModuleKeys));
            }

            $this->role_service->syncPermissions($role, $permissions);
            return redirect('admin/roles')->with('success', Message::SAVE);
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $role = $this->role_service->getByid($id);
        $isBusinessAdmin = getRoleName() == RoleNames::BUSINESSADMIN;
        $enabledModuleKeys = $isBusinessAdmin
            ? SubscriptionModuleRegistry::enabledPermissionModuleKeysFor(Auth::user()->business)
            : null;
        $groupedPermissions = PermissionRegistry::grouped($isBusinessAdmin, $enabledModuleKeys);
        $business = $this->business_service->getAll();
        return view('admin.roles.create', compact('role', 'groupedPermissions', 'business'));
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
    public function byBusiness($business_id)
    {
        try {
            $roles = $this->role_service->getByBusiness($business_id);
            return $this->success(
                Message::SUCCESS,
                $roles
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
}
