<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Mobile\MobileCustomerAccountService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    use ResponseAPI;

    protected $account_service;

    public function __construct(MobileCustomerAccountService $account_service)
    {
        $this->account_service = $account_service;
    }

    public function show(Request $request, $business_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );
        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        try {
            $payload = $this->account_service->getProfilePayload(Auth::user(), $business_id);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::FETCH, $payload);
    }

    public function update(Request $request, $business_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'name' => 'required|string|min:2|max:255',
                'phone' => 'required|string|min:7|max:20',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $payload = $this->account_service->updateProfile(Auth::user(), $business_id, [
                'name' => $request->name,
                'phone' => $request->phone,
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::UPDATE, $payload);
    }

    public function storeAddress(Request $request, $business_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'id' => 'nullable|string',
                'label' => 'nullable|string|max:100',
                'fullName' => 'required_without:full_name|string|max:255',
                'full_name' => 'required_without:fullName|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'required|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'zip' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'isDefault' => 'nullable|boolean',
                'is_default' => 'nullable|boolean',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $address = $this->account_service->saveAddress(Auth::user(), $business_id, $request->all());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::SAVE, $address);
    }

    public function destroyAddress(Request $request, $business_id, $address_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id, 'address_id' => $address_id],
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'address_id' => 'required|string',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $this->account_service->deleteAddress(Auth::user(), $business_id, $address_id);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::DELETE, []);
    }
}
