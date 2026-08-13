<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\RoleService;
use App\Services\Concrete\Admin\UserService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use ResponseAPI;

    protected $user_service;
    protected $role_service;
    protected $business_service;
    protected $branch_service;
    protected $customer_service;

    public function __construct(
        UserService $user_service,
        RoleService $role_service,
        BusinessService $business_service,
        BranchService $branch_service,
        CustomerService $customer_service
    ) {
        $this->user_service = $user_service;
        $this->role_service = $role_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->customer_service = $customer_service;
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
        $branches = $this->branch_service->getAllActive();
        $customer_profile = null;
        return view('admin.users.create', compact('roles', 'business', 'branches', 'customer_profile'));
    }


    public function store(Request $request)
    {
        $role = Role::find($request->role_id);
        $is_customer = $role && $role->name === RoleNames::USER;

        $rules = [

            'name' => 'required',
            'role_id' => 'required|exists:roles,id',
        ];

        // A customer email that already belongs to an existing account is
        // intentionally allowed on create - it reuses that global account
        // instead of erroring, the same rule the OTP onboarding flow follows
        // (see UserService::save()). Only staff roles - and editing an
        // existing customer's own record - enforce strict uniqueness.
        if ($is_customer && empty($request->id)) {
            $rules['email'] = ['required', 'email'];
        } else {
            $rules['email'] = [
                'required',
                'email',
                Rule::unique('users', 'email')
                    ->where('is_deleted', 0)
                    ->ignore($request->id)
            ];
        }

        // Customer accounts never get a staff-typed password here - they set
        // their own via the OTP onboarding flow (see AuthController). Every
        // other (staff) role still requires one up front.
        if (empty($request->id) && !$is_customer) {
            $rules['password'] = 'required|min:6|confirmed';
        }

        if ($role->name == RoleNames::BUSINESSADMIN || $is_customer) {

            $rules['business_id'] = 'required|exists:businesses,business_id';
        }

        if (in_array($role->name, RoleNames::branchLevelRoles())) {

            $rules['branch_id'] = 'required|exists:branches,branch_id';
        }

        if ($is_customer) {
            $rules['code'] = [
                'nullable',
                Rule::unique('customer_profiles', 'code')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id)
                            ->where('is_deleted', 0);
                    }),
            ];
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

        if (empty($request->id) && $request->filled('password')) {
            $obj['password'] = $request->password;
        }
        $obj['status'] = $request->status ?? 'active';

        if ($is_customer) {
            $obj += $request->only([
                'code',
                'company_name',
                'contact_person',
                'address',
                'city',
                'state',
                'country',
                'credit_limit',
                'credit_days',
            ]);
        }

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
        $branches = $this->business_service->getAllActive();

        $customer_profile = null;
        if ($user->hasRole(RoleNames::USER)) {
            // A customer may hold a profile per business - show the one for
            // whichever business this admin is operating in (falls back to
            // the record's own business_id for a Super Admin).
            $profile_business_id = getRoleName() !== RoleNames::SUPERADMIN
                ? Auth::user()->business_id
                : $user->business_id;

            if ($profile_business_id) {
                $customer_profile = $this->customer_service->getProfile($id, $profile_business_id);
            }
        }

        return view('admin.users.create', compact('user', 'roles', 'business', 'branches', 'customer_profile'));
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
