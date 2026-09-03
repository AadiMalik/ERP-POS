<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\WebsiteCheckoutService;
use App\Services\Concrete\Admin\SettingService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    use ResponseAPI;

    protected $checkout_service;
    protected $setting_service;

    public function __construct(WebsiteCheckoutService $checkout_service, SettingService $setting_service)
    {
        $this->checkout_service = $checkout_service;
        $this->setting_service = $setting_service;
    }

    public function paymentMethods(Request $request, $business_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );
        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        $methods = $this->checkout_service->getWebsitePaymentMethods($business_id);
        $settings = $this->setting_service->getWebsitePublicSettings($business_id);

        return $this->success(Message::FETCH, [
            'methods' => $methods,
            'bank_details' => $settings['bank_details'] ?? null,
        ]);
    }

    public function placeOrder(Request $request, $business_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'payment_method_id' => 'nullable|string|exists:payment_methods,payment_method_id',
                'payment_code' => 'nullable|string|max:50',
                'payment_reference' => 'nullable|string|max:100',
                'branch_id' => 'nullable|string|exists:branches,branch_id',
                'full_name' => 'required|string|max:150',
                'email' => 'required|email|max:150',
                'phone' => 'required|string|max:40',
                'address' => 'required|string|max:500',
                'city' => 'required|string|max:100',
                'zip' => 'nullable|string|max:30',
                'country' => 'required|string|max:100',
                'notes' => 'nullable|string|max:1000',
                'client_request_id' => 'nullable|string|max:64',
                'payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
                'use_loyalty_points' => 'nullable|boolean',
            ]
        );

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        if (empty($request->payment_method_id) && empty($request->payment_code)) {
            return $this->validationResponse('A payment method is required.');
        }

        try {
            $payload = $request->only([
                'payment_method_id',
                'payment_code',
                'payment_reference',
                'branch_id',
                'full_name',
                'email',
                'phone',
                'address',
                'city',
                'zip',
                'country',
                'notes',
                'client_request_id',
                'use_loyalty_points',
            ]);

            if (empty($payload['client_request_id'])) {
                $payload['client_request_id'] = (string) Str::uuid();
            }

            $order = $this->checkout_service->placeOrder(
                Auth::id(),
                $business_id,
                $payload,
                $request->file('payment_receipt')
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::SAVE, $order);
    }
}
