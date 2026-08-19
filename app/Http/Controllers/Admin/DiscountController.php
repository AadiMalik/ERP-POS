<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DiscountService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller
{
    use ResponseAPI;

    protected $discount_service;
    protected $business_service;

    public function __construct(
        DiscountService $discount_service,
        BusinessService $business_service
    ) {
        $this->middleware('permission:discount.view')->only(['index', 'getData']);
        $this->middleware('permission:discount.create|discount.edit')->only(['store']);
        $this->middleware('permission:discount.edit')->only(['edit']);
        $this->middleware('permission:discount.delete')->only(['destroy']);
        $this->middleware('permission:discount.status')->only(['status']);
        $this->middleware('module:discount');

        $this->discount_service = $discount_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $business = $this->business_service->getAllActive();

        return view('admin.discount.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->discount_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'discount_id',
            'name',
            'type',
            'value',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['status'] = $request->status ?? 'active';

        try {
            $discount = $this->discount_service->save($obj);
            return $this->success(
                empty($request->discount_id) ? Message::SAVE : Message::UPDATE,
                $discount
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function edit($discount_id)
    {
        try {
            $discount = $this->discount_service->getById($discount_id);
            return $this->success(
                Message::FETCH,
                $discount
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($discount_id)
    {
        try {
            $this->discount_service->status($discount_id);
            return $this->success(
                Message::STATUS,
                []
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function destroy($discount_id)
    {
        try {

            $this->discount_service->delete($discount_id);

            return $this->success(
                Message::DELETE,
                []
            );
        } catch (Exception $e) {

            return $this->error(
                Message::ERROR
            );
        }
    }
}
