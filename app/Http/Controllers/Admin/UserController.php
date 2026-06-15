<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\RoleService;
use App\Services\Concrete\Admin\UserService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use ResponseAPI;

    protected $user_service;
    protected $role_service;
    protected $business_service;
    protected $branch_service;

    public function __construct(
        UserService $user_service,
        RoleService $role_service,
        BusinessService $business_service,
        BranchService $branch_service
    ) {
        $this->user_service = $user_service;
        $this->role_service = $role_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
    }

    public function index()
    {
        $roles = $this->role_service->getAll();
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAll();
        return view('admin.users.index', compact('roles','business','branches'));
    }

    public function getData(Request $request)
    {
        return $this->user_service->getData($request->all());
    }
    public function create()
    {
        $roles = $this->role_service->getAll();
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAll();
        return view('admin.users.create', compact('roles', 'business', 'branches'));
    }


    public function store(Request $request)
    {
        $rules = [

            'name' => 'required',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')
                    ->where('is_deleted', 0)
                    ->ignore($request->id)
            ],
            'role_id' => 'required|exists:roles,id',
        ];

        if (empty($request->id)) {
            $rules['password'] = 'required|min:6|confirmed';
        }
        $role = Role::find($request->role_id);

        if ($role->name == RoleNames::BUSINESSADMIN) {

            $rules['business_id'] = 'required|exists:businesses,business_id';
        }

        if (in_array($role->name, RoleNames::branchLevelRoles())) {

            $rules['branch_id'] = 'required|exists:branches,branch_id';
        }
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }


        $obj = $request->only([
            'id',
            'name',
            'email',
            'phone',
            'business_id',
            'branch_id',
            'role_id'
        ]);

        if (empty($request->id)) {
            $obj['password'] = $request->password;
        }
        $obj['status'] = $request->status ?? 'active';

        // create/update business
        $user = $this->user_service->save($obj);
        return redirect('admin/users')
            ->with('success', empty($request->id) ? Message::SAVE : Message::UPDATE);
    }

    public function edit($id)
    {
        $user = $this->user_service->getById($id);
        $roles = $this->role_service->getAll();
        $business = $this->business_service->getAll();
        $branches = $this->business_service->getAll();
        return view('admin.users.create', compact('user', 'roles','business', 'branches'));
    }
    public function changePassword($id)
    {
        $user = $this->user_service->getById($id);
        return view('admin.users.change_password', compact('user'));
    }

    public function updatePassword(Request $request)
    {
        $rules = [
            'id' => 'required|exists:users,id',
            'password' => 'required|min:6|confirmed'
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }


        $obj = $request->only([
            'id',
            'password'
        ]);

        // create/update business
        $user = $this->user_service->changePassword($obj);
        return redirect('admin/users')
            ->with('success', empty($request->id) ? Message::SAVE : Message::UPDATE);
    }

    public function status($id)
    {
        try {
            $this->user_service->status($id);
            return $this->success(
                Message::STATUS,
                []
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function destroy($id)
    {
        try {
            $this->user_service->delete($id);
            return $this->success(
                Message::DELETE,
                []
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
}
