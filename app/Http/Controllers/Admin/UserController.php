<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
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

    public function __construct(
        UserService $user_service,
        RoleService $role_service,
        BusinessService $business_service
    ) {
        $this->user_service = $user_service;
        $this->role_service = $role_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $roles = $this->role_service->getAll();
        return view('admin.users.index', compact('roles'));
    }

    public function getData(Request $request)
    {
        return $this->user_service->getData($request->all());
    }
    public function create()
    {
        $roles = $this->role_service->getAll();
        $business = $this->business_service->getAll();
        return view('admin.users.create', compact('roles','business'));
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
            ]
        ];

        if (empty($request->id)) {
            $rules['password'] = 'required|min:6|confirmed';
        }

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }


        $obj = $request->only([
            'id',
            'name',
            'email',
            'business_id',
            'branch_id'
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
        return view('admin.users.create', compact('user', 'roles'));
    }

    public function changePassword(Request $request)
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
