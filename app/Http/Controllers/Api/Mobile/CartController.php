<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Mobile\MobileCartService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    use ResponseAPI;

    protected $cart_service;

    public function __construct(MobileCartService $cart_service)
    {
        $this->cart_service = $cart_service;
    }

    public function show(Request $request, $business_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'branch_id' => 'nullable|string|exists:branches,branch_id',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $cart = $this->cart_service->getCart(
                Auth::id(),
                $business_id,
                $request->branch_id
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::FETCH, $cart);
    }

    public function store(Request $request, $business_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'product_id' => 'required|string|exists:products,product_id',
                'product_variation_id' => 'required|string|exists:product_variations,product_variation_id',
                'quantity' => 'nullable|numeric|min:0.001',
                'branch_id' => 'nullable|string|exists:branches,branch_id',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $cart = $this->cart_service->addItem(
                Auth::id(),
                $business_id,
                $request->product_id,
                $request->product_variation_id,
                (float) ($request->quantity ?? 1),
                $request->branch_id
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::SAVE, $cart);
    }

    public function update(Request $request, $business_id, $cart_item_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), [
                'business_id' => $business_id,
                'cart_item_id' => $cart_item_id,
            ]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'cart_item_id' => 'required|string',
                'quantity' => 'required|numeric|min:0',
                'branch_id' => 'nullable|string|exists:branches,branch_id',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $cart = $this->cart_service->updateItem(
                Auth::id(),
                $business_id,
                $cart_item_id,
                (float) $request->quantity,
                $request->branch_id
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::UPDATE, $cart);
    }

    public function destroy(Request $request, $business_id, $cart_item_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id, 'cart_item_id' => $cart_item_id],
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'cart_item_id' => 'required|string',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $cart = $this->cart_service->removeItem(Auth::id(), $business_id, $cart_item_id);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::DELETE, $cart);
    }

    public function clear(Request $request, $business_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $cart = $this->cart_service->clear(Auth::id(), $business_id);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::DELETE, $cart);
    }
}
