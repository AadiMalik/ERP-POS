<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\LossReasonService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LossReasonController extends Controller
{
    use ResponseAPI;

    protected $loss_reason_service;
    protected $business_service;

    public function __construct(
        LossReasonService $loss_reason_service,
        BusinessService $business_service
    ) {
        $this->middleware('permission:loss-reason.view')->only(['index', 'getData', 'edit']);
        $this->middleware('permission:loss-reason.create|loss-reason.edit')->only(['store']);
        $this->middleware('permission:loss-reason.delete')->only(['destroy']);

        $this->loss_reason_service = $loss_reason_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();

        return view('admin.loss_reason.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->loss_reason_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('loss_reasons', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->loss_reason_id, 'loss_reason_id'),
            ],
            'status' => ['nullable', 'in:active,inactive'],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $obj = $request->only(['loss_reason_id', 'business_id', 'name', 'status']);
            $loss_reason = $this->loss_reason_service->save($obj);

            return $this->success(
                empty($request->loss_reason_id) ? Message::SAVE : Message::UPDATE,
                $loss_reason
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function edit($loss_reason_id)
    {
        try {
            $loss_reason = $this->loss_reason_service->getById($loss_reason_id);
            return $this->success(Message::FETCH, $loss_reason);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($loss_reason_id)
    {
        try {
            $this->loss_reason_service->delete($loss_reason_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function byBusiness($business_id)
    {
        try {
            $loss_reasons = $this->loss_reason_service->getActiveByBusiness($business_id);
            return $this->success(Message::SUCCESS, $loss_reasons);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
