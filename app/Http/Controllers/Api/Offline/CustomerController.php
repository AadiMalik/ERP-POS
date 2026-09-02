<?php

namespace App\Http\Controllers\Api\Offline;

use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\UserService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    use ResponseAPI;

    protected $user_service;
    protected $customer_service;

    public function __construct(UserService $user_service, CustomerService $customer_service)
    {
        $this->middleware('permission:order.customer.change');

        $this->user_service = $user_service;
        $this->customer_service = $customer_service;
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $device = $request->attributes->get('pos_device');
            $role_id = Role::where('name', RoleNames::USER)->whereNull('business_id')->value('id');

            if (empty($role_id)) {
                return $this->error('Customer role is not configured.');
            }

            $customer = $this->user_service->save([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role_id' => $role_id,
                'business_id' => $device->business_id,
                'status' => 'active',
            ]);

            $profile = $this->customer_service->getProfile($customer->id, $device->business_id);

            return $this->success('Customer created.', [
                'user_id' => $customer->id,
                'code' => $profile->code ?? '',
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'credit_limit' => $profile->credit_limit ?? 0,
                'is_walkin' => $profile->is_walkin ?? 0,
                'credit_days' => $profile->credit_days ?? 0,
                'store_credit_balance' => $profile->store_credit_balance ?? 0,
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
