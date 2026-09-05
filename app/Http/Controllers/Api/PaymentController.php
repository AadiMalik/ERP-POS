<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\PaymentService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Website Payment Gateway API: list active gateways, start a payment against
 * an already-created (see CheckoutController::placeOrder()) hold order, poll
 * status, and re-verify directly with the provider. Mirrored by
 * Api\Mobile\PaymentController for the mobile app - same underlying
 * PaymentService, only the `platform` flag differs.
 */
class PaymentController extends Controller
{
    use ResponseAPI;

    protected $payment_service;
    protected $platform = 'website';

    public function __construct(PaymentService $payment_service)
    {
        $this->payment_service = $payment_service;
    }

    public function gateways(Request $request, $business_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );
        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        $gateways = $this->payment_service->listAvailableGateways($business_id, $this->platform, $request->currency);

        return $this->success(Message::FETCH, $gateways);
    }

    public function initiate(Request $request, $business_id, $order_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id, 'order_id' => $order_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'order_id' => 'required|string|exists:orders,order_id',
                'payment_gateway_id' => 'required|string|exists:payment_gateways,payment_gateway_id',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $result = $this->payment_service->initiate($business_id, $order_id, $request->payment_gateway_id, $this->platform, Auth::id());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::SUCCESS, $result);
    }

    public function status(Request $request, $business_id, $payment_transaction_id)
    {
        try {
            $result = $this->payment_service->status($business_id, $payment_transaction_id, Auth::id());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::FETCH, $result);
    }

    public function verify(Request $request, $business_id, $payment_transaction_id)
    {
        try {
            $result = $this->payment_service->verify($business_id, $payment_transaction_id, Auth::id());
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::SUCCESS, $result);
    }
}
