<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Mobile\MobileOrderService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CustomerOrderController extends Controller
{
    use ResponseAPI;

    protected $order_service;

    public function __construct(MobileOrderService $order_service)
    {
        $this->order_service = $order_service;
    }

    public function index(Request $request, $business_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );
        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        $result = $this->order_service->list(Auth::id(), $business_id, $request->only([
            'page',
            'per_page',
            'status',
        ]));

        return $this->success(Message::FETCH, $result);
    }

    public function show(Request $request, $business_id, $order_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id, 'order_id' => $order_id],
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'order_id' => 'required|string',
            ]
        );
        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        try {
            $order = $this->order_service->find(Auth::id(), $business_id, $order_id);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 404);
        }

        return $this->success(Message::FETCH, $order);
    }

    public function track(Request $request, $business_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'order_number' => 'required|string|max:100',
                'email' => 'nullable|email|max:150',
                'phone' => 'nullable|string|max:40',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $order = $this->order_service->track(
                $business_id,
                $request->order_number,
                $request->email,
                $request->phone
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 404);
        }

        return $this->success(Message::FETCH, $order);
    }
}
