<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ExpenseCategoryService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    use ResponseAPI;

    protected $expense_category_service;
    protected $business_service;
    protected $account_service;

    public function __construct(
        ExpenseCategoryService $expense_category_service,
        BusinessService $business_service,
        AccountService $account_service
    ) {
        $this->middleware('permission:expense-category.manage');

        $this->expense_category_service = $expense_category_service;
        $this->business_service = $business_service;
        $this->account_service = $account_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $accounts = $this->account_service->getChildByBusiness(Auth::user()->business_id);

        return view('admin.expense_category.index', compact('business', 'accounts'));
    }

    public function getData(Request $request)
    {
        return $this->expense_category_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('expense_categories', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->expense_category_id, 'expense_category_id'),
            ],
            'code'        => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'use_default_account' => ['nullable', 'boolean'],
            'account_id'  => ['nullable', Rule::exists('accounts', 'account_id')->where('is_deleted', 0)],
            'status'      => ['nullable', 'in:active,inactive'],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        if (!$request->boolean('use_default_account') && empty($request->account_id)) {
            return $this->validationResponse('Please select an account, or enable "Use Business Default Expense Account".');
        }

        try {
            $obj = $request->only([
                'expense_category_id',
                'business_id',
                'name',
                'code',
                'description',
                'account_id',
                'use_default_account',
                'status',
            ]);

            $obj['use_default_account'] = $request->boolean('use_default_account');

            $expense_category = $this->expense_category_service->save($obj);

            return $this->success(
                empty($request->expense_category_id) ? Message::SAVE : Message::UPDATE,
                $expense_category
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function edit($expense_category_id)
    {
        try {
            $expense_category = $this->expense_category_service->getById($expense_category_id);
            return $this->success(Message::FETCH, $expense_category);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($expense_category_id)
    {
        try {
            $this->expense_category_service->delete($expense_category_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function reset()
    {
        try {
            $this->expense_category_service->resetBusinessExpenseCategory();
            return $this->success(Message::SUCCESS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function byBusiness($business_id)
    {
        try {
            $expense_category = $this->expense_category_service->getActiveByBusiness($business_id);
            return $this->success(Message::SUCCESS, $expense_category);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
