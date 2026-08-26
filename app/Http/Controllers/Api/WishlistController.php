<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\WishlistService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    use ResponseAPI;

    protected $wishlist_service;

    public function __construct(WishlistService $wishlist_service)
    {
        $this->wishlist_service = $wishlist_service;
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

        $items = $this->wishlist_service->list(Auth::id(), $business_id);

        return $this->success(Message::FETCH, ['items' => $items]);
    }

    public function store(Request $request, $business_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'product_id' => 'required|string|exists:products,product_id',
                'product_variation_id' => 'nullable|string|exists:product_variations,product_variation_id',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $result = $this->wishlist_service->add(
                Auth::id(),
                $business_id,
                $request->product_id,
                $request->product_variation_id
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::SAVE, $result);
    }

    public function destroy(Request $request, $business_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'product_id' => 'required|string',
                'product_variation_id' => 'nullable|string',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $this->wishlist_service->remove(
                Auth::id(),
                $business_id,
                $request->product_id,
                $request->product_variation_id
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::DELETE, []);
    }

    public function toggle(Request $request, $business_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'product_id' => 'required|string|exists:products,product_id',
                'product_variation_id' => 'nullable|string|exists:product_variations,product_variation_id',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $result = $this->wishlist_service->toggle(
                Auth::id(),
                $business_id,
                $request->product_id,
                $request->product_variation_id
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::SUCCESS, $result);
    }

    public function status(Request $request, $business_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'product_id' => 'required|string',
                'product_variation_id' => 'nullable|string',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $result = $this->wishlist_service->status(
            Auth::id(),
            $business_id,
            $request->product_id,
            $request->product_variation_id
        );

        return $this->success(Message::FETCH, $result);
    }
}
