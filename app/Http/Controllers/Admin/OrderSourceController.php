<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\OrderSourceService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderSourceController extends Controller
{
    use ResponseAPI;

    protected $order_source_service;
    protected $business_service;

    public function __construct(OrderSourceService $order_source_service, BusinessService $business_service)
    {
        $this->middleware('permission:order-source.view')->only(['index', 'getData']);
        $this->middleware('permission:order-source.create|order-source.edit')->only(['store']);
        $this->middleware('permission:order-source.edit')->only(['edit']);
        $this->middleware('permission:order-source.delete')->only(['destroy']);
        $this->middleware('permission:order-source.status')->only(['status']);

        $this->order_source_service = $order_source_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $business = $this->business_service->getAllActive();
        return view('admin.order-source.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->order_source_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('order_sources', 'code')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->order_source_id, 'order_source_id')
            ],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'order_source_id',
            'name',
            'code',
            'sort_order',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['is_default'] = $request->boolean('is_default');
        $obj['status'] = $request->status ?? 'active';

        try {
            $order_source = $this->order_source_service->save($obj);
            return $this->success(
                empty($request->order_source_id) ? Message::SAVE : Message::UPDATE,
                $order_source
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function edit($order_source_id)
    {
        try {
            $order_source = $this->order_source_service->getById($order_source_id);
            return $this->success(
                Message::FETCH,
                $order_source
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($order_source_id)
    {
        try {
            $this->order_source_service->status($order_source_id);
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

    public function destroy($order_source_id)
    {
        try {

            $this->order_source_service->delete($order_source_id);

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
