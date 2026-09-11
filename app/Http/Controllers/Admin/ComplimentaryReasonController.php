<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ComplimentaryReasonService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ComplimentaryReasonController extends Controller
{
    use ResponseAPI;

    protected $complimentary_reason_service;
    protected $business_service;

    public function __construct(
        ComplimentaryReasonService $complimentary_reason_service,
        BusinessService $business_service
    ) {
        $this->middleware('permission:complimentary-reason.view')->only(['index', 'getData', 'edit']);
        $this->middleware('permission:complimentary-reason.create|complimentary-reason.edit')->only(['store']);
        $this->middleware('permission:complimentary-reason.delete')->only(['destroy']);

        $this->complimentary_reason_service = $complimentary_reason_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();

        return view('admin.complimentary_reason.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->complimentary_reason_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('complimentary_reasons', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->complimentary_reason_id, 'complimentary_reason_id'),
            ],
            'status' => ['nullable', 'in:active,inactive'],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $obj = $request->only(['complimentary_reason_id', 'business_id', 'name', 'status']);
            $reason = $this->complimentary_reason_service->save($obj);

            return $this->success(
                empty($request->complimentary_reason_id) ? Message::SAVE : Message::UPDATE,
                $reason
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function edit($complimentary_reason_id)
    {
        try {
            $reason = $this->complimentary_reason_service->getById($complimentary_reason_id);
            return $this->success(Message::FETCH, $reason);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($complimentary_reason_id)
    {
        try {
            $this->complimentary_reason_service->delete($complimentary_reason_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function byBusiness($business_id)
    {
        try {
            $reasons = $this->complimentary_reason_service->getActiveByBusiness($business_id);
            return $this->success(Message::SUCCESS, $reasons);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
