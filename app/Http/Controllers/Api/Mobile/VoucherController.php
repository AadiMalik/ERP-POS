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

class VoucherController extends Controller
{
    use ResponseAPI;

    protected $cart_service;

    public function __construct(MobileCartService $cart_service)
    {
        $this->cart_service = $cart_service;
    }

    public function search(Request $request, $business_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'term' => 'nullable|string|max:50',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $term = trim((string) $request->input('term', ''));
        if ($term === '') {
            return $this->success(Message::FETCH, []);
        }

        try {
            $results = $this->cart_service->searchVouchers(Auth::id(), $business_id, $term);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::FETCH, $results);
    }

    public function eligible(Request $request, $business_id)
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
            $results = $this->cart_service->eligibleVouchers(
                Auth::id(),
                $business_id,
                $request->branch_id
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::FETCH, $results);
    }

    public function preview(Request $request, $business_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'voucher_code' => 'nullable|string|max:50',
                'voucher_id' => 'nullable|string|exists:vouchers,voucher_id',
                'branch_id' => 'nullable|string|exists:branches,branch_id',
                'payment_method_id' => 'nullable|string|exists:payment_methods,payment_method_id',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        if (empty($request->voucher_code) && empty($request->voucher_id)) {
            return $this->validationResponse('A voucher code is required.');
        }

        try {
            $preview = $this->cart_service->previewVoucher(
                Auth::id(),
                $business_id,
                $request->only(['voucher_code', 'voucher_id', 'branch_id', 'payment_method_id'])
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::FETCH, $preview);
    }

    public function apply(Request $request, $business_id)
    {
        $validate = Validator::make(
            array_merge($request->all(), ['business_id' => $business_id]),
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'voucher_code' => 'nullable|string|max:50',
                'voucher_id' => 'nullable|string|exists:vouchers,voucher_id',
                'branch_id' => 'nullable|string|exists:branches,branch_id',
            ]
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        if (empty($request->voucher_code) && empty($request->voucher_id)) {
            return $this->validationResponse('A voucher code is required.');
        }

        try {
            $cart = $this->cart_service->applyVoucher(
                Auth::id(),
                $business_id,
                $request->voucher_code,
                $request->voucher_id,
                $request->branch_id
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::SAVE, $cart);
    }

    public function remove(Request $request, $business_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $cart = $this->cart_service->removeVoucher(Auth::id(), $business_id);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::DELETE, $cart);
    }
}
