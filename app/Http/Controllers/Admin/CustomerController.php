<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\UserService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Dedicated Customer CRUD. Under the hood a "customer" is still a `users`
 * row with the RoleNames::USER role plus a business-scoped CustomerProfile
 * (see CustomerService/UserService docblocks) - this controller is a thin,
 * customer-only front end over that same UserService::save() write path so
 * POS's quickCreateCustomer() and the Admin Users screen keep behaving
 * exactly as before.
 */
class CustomerController extends Controller
{
    use ResponseAPI;

    protected $customer_service;
    protected $user_service;
    protected $business_service;

    public function __construct(
        CustomerService $customer_service,
        UserService $user_service,
        BusinessService $business_service
    ) {
        $this->middleware('permission:customer.view')->only(['index', 'getData', 'byBusiness', 'show']);
        $this->middleware('permission:customer.create')->only(['create']);
        $this->middleware('permission:customer.create|customer.edit')->only(['store']);
        $this->middleware('permission:customer.edit')->only(['edit']);
        $this->middleware('permission:customer.delete')->only(['destroy']);
        $this->middleware('permission:customer.status')->only(['status']);

        $this->customer_service = $customer_service;
        $this->user_service = $user_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $business = $this->business_service->getAllActive();
        return view('admin.customer.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->customer_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAllActive();
        return view('admin.customer.create', compact('business'));
    }

    protected function customerRoleId()
    {
        return Role::where('name', RoleNames::USER)->whereNull('business_id')->value('id');
    }

    public function store(Request $request)
    {
        $role_id = $this->customerRoleId();

        if (empty($role_id)) {
            return redirect()->back()->withErrors(['name' => 'Customer role is not configured.'])->withInput();
        }

        $rules = [
            'name' => 'required',
            'email' => 'required|email',
            'business_id' => 'required|exists:businesses,business_id',
            'code' => [
                'nullable',
                Rule::unique('customer_profiles', 'code')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id)
                            ->where('is_deleted', 0);
                    }),
            ],
        ];

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
            'code',
            'company_name',
            'contact_person',
            'address',
            'city',
            'state',
            'country',
            'shipping_address',
            'shipping_city',
            'shipping_state',
            'shipping_country',
            'credit_limit',
            'credit_days',
            'opening_balance',
            'opening_balance_type',
            'payment_terms',
            'notes',
        ]);

        $obj['role_id'] = $role_id;
        $obj['status'] = $request->status ?? 'active';

        try {
            // UserService::save() creates/updates the `users` row, assigns the
            // Customer role, and calls CustomerService::upsertProfile() with
            // this same $obj for the business-scoped profile fields - the
            // exact path POS's quickCreateCustomer() and the legacy Admin
            // Users screen already use, kept as the single source of truth.
            $customer = $this->user_service->save($obj);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['name' => $e->getMessage()])->withInput();
        }

        return redirect('admin/customer')
            ->with('success', empty($request->id) ? Message::SAVE : Message::UPDATE);
    }

    public function edit($user_id)
    {
        $user = $this->user_service->getByid($user_id);
        $business = $this->business_service->getAllActive();

        $profile_business_id = getRoleName() !== RoleNames::SUPERADMIN
            ? Auth::user()->business_id
            : $user->business_id;

        $customer_profile = $profile_business_id
            ? $this->customer_service->getProfile($user_id, $profile_business_id)
            : null;

        return view('admin.customer.create', compact('user', 'business', 'customer_profile'));
    }

    public function show($user_id)
    {
        $business_id = getRoleName() !== RoleNames::SUPERADMIN
            ? Auth::user()->business_id
            : request('business_id');

        $user = $this->user_service->getByid($user_id);
        $customer_profile = $business_id ? $this->customer_service->getProfile($user_id, $business_id) : null;
        $history = $business_id ? $this->customer_service->getCustomerHistory($user_id, $business_id) : [];
        $timeline = $business_id ? $this->customer_service->getCustomerTimeline($user_id, $business_id) : [];
        $ledger = $business_id ? $this->customer_service->getCustomerLedger($user_id, $business_id) : null;

        return view('admin.customer.show', compact('user', 'customer_profile', 'history', 'timeline', 'ledger', 'business_id'));
    }

    public function status($user_id)
    {
        try {
            $this->customer_service->status($user_id, request('business_id'));
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($user_id)
    {
        try {
            $this->customer_service->delete($user_id, request('business_id'));
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function byBusiness($business_id)
    {
        try {
            $customers = $this->customer_service->getAllActive($business_id);
            return $this->success(Message::SUCCESS, $customers);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
