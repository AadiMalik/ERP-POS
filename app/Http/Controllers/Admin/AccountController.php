<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\AccountTypeService;
use App\Services\Concrete\Admin\BusinessService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    use ResponseAPI;
    protected $account_service;
    protected $account_type_service;
    protected $business_service;
    public function __construct(
        AccountService  $account_service,
        AccountTypeService  $account_type_service,
        BusinessService $business_service
    ) {
        $this->account_service = $account_service;
        $this->account_type_service = $account_type_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $account_types = $this->account_type_service->getAll();
        $data = $this->account_service->getData();
        return view('admin.account.index', compact('business', 'data', 'account_types'));
    }

    public function storeParent(Request $request)
    {
        $rules = [
            'parent_account_type_id' => 'required|exists:account_types,account_type_id',
            'parent_account_sub_type_id' => 'required|exists:account_sub_types,account_sub_type_id',
            'parent_code' => [
                'required',
                Rule::unique('accounts', 'code')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->parent_business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->parent_account_id, 'account_id')
            ],
            'parent_name' => 'required',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = [
            'account_id' => $request->parent_account_id,
            'business_id' => $request->parent_business_id ??  Auth::user()->business_id,
            'account_type_id' => $request->parent_account_type_id,
            'account_sub_type_id' => $request->parent_account_sub_type_id,
            'code' => $request->parent_code,
            'name' => $request->parent_name,
            'description' => $request->parent_description
        ];

        // save parent account
        $parent_account = $this->account_service->save($obj);
        return $this->success(
            empty($request->account_id) ? Message::SAVE : Message::UPDATE,
            $parent_account
        );
    }

    public function storeChild(Request $request)
    {
        $rules = [
            'child_account_type_id' => 'required|exists:account_types,account_type_id',
            'child_account_sub_type_id' => 'required|exists:account_sub_types,account_sub_type_id',
            'child_parent_account_id' => 'required|exists:accounts,account_id',
            'child_code' => [
                'required',
                Rule::unique('accounts', 'code')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->child_business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->child_account_id, 'account_id')
            ],
            'child_name' => 'required',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = [
            'account_id' => $request->child_account_id,
            'business_id' => $request->child_business_id ??  Auth::user()->business_id,
            'account_type_id' => $request->child_account_type_id,
            'parent_account_id' => $request->child_parent_account_id,
            'account_sub_type_id' => $request->child_account_sub_type_id,
            'code' => $request->child_code,
            'name' => $request->child_name,
            'description' => $request->child_description
        ];

        // save child account
        $child_account = $this->account_service->save($obj);
        return $this->success(
            empty($request->account_id) ? Message::SAVE : Message::UPDATE,
            $child_account
        );
    }

    public function edit($account_id)
    {
        try {
            $account = $this->account_service->getById($account_id);
            return $this->success(
                Message::FETCH,
                $account
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($account_id)
    {
        try {
            $this->account_service->status($account_id);
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

    public function destroy($account_id)
    {
        try {

            $this->account_service->delete($account_id);

            return $this->success(
                Message::DELETE,
                []
            );
        } catch (Exception $e) {

            return $this->error(
                $e->getMessage()
            );
        }
    }

    public function parentByAccountType($account_type_id)
    {
        try {
            $accounts = $this->account_service->getParentByAccountType($account_type_id);
            return $this->success(
                Message::SUCCESS,
                $accounts
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function parentByAccountSubType($account_sub_type_id)
    {
        try {
            $accounts = $this->account_service->getParentByAccountSubType($account_sub_type_id);
            return $this->success(
                Message::SUCCESS,
                $accounts
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
}
