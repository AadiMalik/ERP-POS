<?php

namespace App\Http\Controllers\Api\Offline;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Concrete\Admin\OrderService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use ResponseAPI;

    protected $order_service;

    public function __construct(OrderService $order_service)
    {
        $this->middleware('permission:order.create|order.edit')->only(['store']);
        $this->middleware('permission:order.complete')->only(['complete']);
        $this->middleware('permission:order.hold')->only(['hold']);

        $this->order_service = $order_service;
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'products' => ['required', 'array', 'min:1'],
            'register_session_id' => ['required', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $device = $request->attributes->get('pos_device');
            $key = $request->input('idempotency_key') ?: (string) Str::uuid();

            $existing = Order::where('business_id', $device->business_id)
                ->where('client_request_id', $key)
                ->first();

            if ($existing) {
                return $this->success('Order already synced.', $existing);
            }

            $payload = $request->all();
            $payload['client_request_id'] = $key;
            $payload['pos_device_id'] = $device->pos_device_id;
            $payload['offline_local_id'] = $request->input('local_id');

            $order = $this->order_service->save($payload);

            return $this->success('Order saved.', $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function complete(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'order_id' => ['required', 'string'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $order = $this->order_service->post($request->all());

            return $this->success('Order completed.', $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function hold(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'order_id' => ['required', 'string'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $order = $this->order_service->hold($request->order_id);

            return $this->success('Order held.', $order);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
