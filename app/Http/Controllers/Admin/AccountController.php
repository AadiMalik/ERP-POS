<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
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
        $this->middleware('permission:account.view')->only(['index', 'edit', 'nextCode', 'parentByAccountType', 'parentByAccountSubType']);
        $this->middleware('permission:account.create|account.edit')->only(['storeParent', 'storeChild']);
        $this->middleware('permission:account.delete')->only(['destroy']);
        $this->middleware('permission:account.status')->only(['status']);
        $this->middleware('module:account');

        $this->account_service = $account_service;
        $this->account_type_service = $account_type_service;
        $this->business_service = $business_service;
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $account_types = $this->account_type_service->getAll();

        // Super Admin defaults to the global template (business_id = null)
        // and can switch to a specific business's live COA via ?business_id=.
        // Other roles keep the existing role-scoped (own business) view.
        $selected_business_id = false;
        if (getRoleName() == RoleNames::SUPERADMIN) {
            $selected_business_id = $request->filled('business_id') ? $request->business_id : null;
        }

        $data = $this->account_service->getData($selected_business_id);
        return view('admin.account.index', compact('business', 'data', 'account_types', 'selected_business_id'));
    }

    public function storeParent(Request $request)
    {
        $business_id = $this->resolveBusinessId($request, 'parent_business_id');

        $rules = [
            'parent_account_type_id' => 'required|exists:account_types,account_type_id',
            'parent_account_sub_type_id' => 'required|exists:account_sub_types,account_sub_type_id',
            'parent_code' => [
                'required',
                'regex:/^[0-9]+$/',
                Rule::unique('accounts', 'code')
                    ->where(function ($query) use ($business_id) {
                        return $query->where('business_id', $business_id)
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
            'business_id' => $business_id,
            'account_type_id' => $request->parent_account_type_id,
            'account_sub_type_id' => $request->parent_account_sub_type_id,
            'code' => $request->parent_code,
            'name' => $request->parent_name,
            'description' => $request->parent_description
        ];

        try {
            // save parent account
            $parent_account = $this->account_service->save($obj);
            return $this->success(
                empty($request->parent_account_id) ? Message::SAVE : Message::UPDATE,
                $parent_account
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function storeChild(Request $request)
    {
        $business_id = $this->resolveBusinessId($request, 'child_business_id');

        $rules = [
            'child_account_type_id' => 'required|exists:account_types,account_type_id',
            'child_account_sub_type_id' => 'required|exists:account_sub_types,account_sub_type_id',
            'child_parent_account_id' => 'required|exists:accounts,account_id',
            'child_name' => 'required',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = [
            'account_id' => $request->child_account_id,
            'business_id' => $business_id,
            'account_type_id' => $request->child_account_type_id,
            'parent_account_id' => $request->child_parent_account_id,
            'account_sub_type_id' => $request->child_account_sub_type_id,
            // 'code' is intentionally omitted - AccountService::save() always
            // derives it from the selected parent account.
            'name' => $request->child_name,
            'description' => $request->child_description
        ];

        try {
            // save child account
            $child_account = $this->account_service->save($obj);
            return $this->success(
                empty($request->child_account_id) ? Message::SAVE : Message::UPDATE,
                $child_account
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
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

    /**
     * Preview of the code a new child would receive under the given parent
     * account, used by the Add/Edit Child form to display the code before
     * saving. The final code is always recomputed authoritatively on save.
     */
    public function nextCode($parent_account_id)
    {
        try {
            $code = $this->account_service->previewNextChildCode($parent_account_id);
            return $this->success(
                Message::SUCCESS,
                ['code' => $code]
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
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

    /**
     * Resolves the business an Add Parent/Add Child submission targets. Only
     * Super Admin can target the global template (business_id = null, via the
     * "System Template (Global)" option); every other role is always pinned
     * to their own business regardless of what was submitted.
     */
    private function resolveBusinessId(Request $request, string $field)
    {
        if (getRoleName() == RoleNames::SUPERADMIN) {
            $value = $request->input($field);
            return ($value === null || $value === '') ? null : $value;
        }

        return Auth::user()->business_id;
    }
}
