<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountSubTypeService;
use App\Services\Concrete\Admin\AccountTypeService;
use App\Services\Concrete\Admin\BusinessService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AccountSubTypeController extends Controller
{
    use ResponseAPI;
    protected $account_sub_type_service;
    protected $account_type_service;
    protected $business_service;
    public function __construct(
        AccountSubTypeService $account_sub_type_service,
        AccountTypeService  $account_type_service,
        BusinessService $business_service
    ) {
        $this->account_sub_type_service = $account_sub_type_service;
        $this->account_type_service = $account_type_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $account_types = $this->account_type_service->getAll();
        $business = $this->business_service->getAll();
        return view('admin.account_sub_type.index', compact('account_types','business'));
    }

    public function getData(Request $request)
    {
        return $this->account_sub_type_service->getData($request->all());
    }
    public function store(Request $request)
    {
        $rules = [
            'account_sub_type_id' => 'required|exists:account_sub_types,account_sub_type_id',
            'code' => [
                'required',
                Rule::unique('account_sub_types', 'code')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->account_sub_type_id, 'account_sub_type_id')
            ]
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'account_sub_type_id',
            'code',
        ]);

        // update account sub type
        $account_sub_type = $this->account_sub_type_service->save($obj);
        return $this->success(
            empty($request->account_sub_type_id) ? Message::SAVE : Message::UPDATE,
            $account_sub_type
        );
    }

    public function edit($account_sub_type_id)
    {
        try {
            $account_sub_type = $this->account_sub_type_service->getById($account_sub_type_id);
            return $this->success(
                Message::FETCH,
                $account_sub_type
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    public function destroy($account_sub_type_id)
    {
        try {

            $this->account_sub_type_service->delete($account_sub_type_id);

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

    public function reset()
    {
        try {
            $account_sub_type = $this->account_sub_type_service->resetBusinessAccountSubType();
            return $this->success(
                Message::SUCCESS,
                $account_sub_type
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function byAccountType($account_type_id)
    {
        try {
            $account_sub_type = $this->account_sub_type_service->getByAccountType($account_type_id);
            return $this->success(
                Message::SUCCESS,
                $account_sub_type
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byBusiness($business_id)
    {
        try {
            $account_sub_type = $this->account_sub_type_service->getByBusiness($business_id);
            return $this->success(
                Message::SUCCESS,
                $account_sub_type
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
}
