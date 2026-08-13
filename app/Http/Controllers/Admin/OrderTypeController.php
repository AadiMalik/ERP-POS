<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\OrderTypeService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderTypeController extends Controller
{
    use ResponseAPI;

    protected $order_type_service;
    protected $business_service;

    public function __construct(OrderTypeService $order_type_service, BusinessService $business_service)
    {
        $this->order_type_service = $order_type_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $business = $this->business_service->getAllActive();
        return view('admin.order-type.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->order_type_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('order_types', 'code')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->order_type_id, 'order_type_id')
            ],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'order_type_id',
            'name',
            'code',
            'sort_order',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['is_default'] = $request->boolean('is_default');
        $obj['status'] = $request->status ?? 'active';

        try {
            $order_type = $this->order_type_service->save($obj);
            return $this->success(
                empty($request->order_type_id) ? Message::SAVE : Message::UPDATE,
                $order_type
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function edit($order_type_id)
    {
        try {
            $order_type = $this->order_type_service->getById($order_type_id);
            return $this->success(
                Message::FETCH,
                $order_type
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($order_type_id)
    {
        try {
            $this->order_type_service->status($order_type_id);
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

    public function destroy($order_type_id)
    {
        try {

            $this->order_type_service->delete($order_type_id);

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
