<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\SaleTypeService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SaleTypeController extends Controller
{
    use ResponseAPI;

    protected $sale_type_service;

    public function __construct(SaleTypeService $sale_type_service)
    {
        $this->middleware('permission:sale-type.view')->only(['getData', 'list']);
        $this->middleware('permission:sale-type.create|sale-type.edit')->only(['store']);
        $this->middleware('permission:sale-type.edit')->only(['edit']);
        $this->middleware('permission:sale-type.delete')->only(['destroy']);
        $this->middleware('permission:sale-type.status')->only(['status']);

        $this->sale_type_service = $sale_type_service;
    }

    public function getData(Request $request)
    {
        return $this->sale_type_service->getData($request->all());
    }

    /**
     * Plain list (active + inactive) for the inline Sale Types manager in
     * the POS Settings tab.
     */
    public function list(Request $request)
    {
        $business_id = $request->business_id ?? Auth::user()->business_id;

        return $this->success(Message::FETCH, $this->sale_type_service->getAll($business_id));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sale_types', 'code')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->sale_type_id, 'sale_type_id')
            ],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'sale_type_id',
            'name',
            'code',
            'sort_order',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['is_default'] = $request->boolean('is_default');
        $obj['status'] = $request->status ?? 'active';

        try {
            $sale_type = $this->sale_type_service->save($obj);
            return $this->success(
                empty($request->sale_type_id) ? Message::SAVE : Message::UPDATE,
                $sale_type
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function edit($sale_type_id)
    {
        try {
            $sale_type = $this->sale_type_service->getById($sale_type_id);
            return $this->success(Message::FETCH, $sale_type);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($sale_type_id)
    {
        try {
            $this->sale_type_service->status($sale_type_id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($sale_type_id)
    {
        try {
            $this->sale_type_service->delete($sale_type_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
