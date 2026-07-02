<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Repository\Repository;
use Yajra\DataTables\DataTables;
use App\Enums\RoleNames;
use App\Enums\AccountTypes;
use App\Models\Account;
use App\Models\AccountType;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AccountService
{

    protected $model_account;
    protected $model_account_type;
    protected $with = ['business', 'accountType', 'accountSubType', 'parentAccount', 'childAccounts'];
    public function __construct()
    {
        // set the model
        $this->model_account = new Repository(new Account());
        $this->model_account_type = new Repository(new AccountType());
    }

    public function getData()
    {
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];
        $data = $this->model_account_type->getModel()::with([
            'accountSubTypes.accounts' => function ($q) {
                $q->whereNull('parent_account_id')
                    ->where('is_deleted', 0)
                    ->with('childAccounts');
            }
        ])->where('is_deleted', 0);
        $account_types = applyRoleScope($data, $allow_roles);
        return $account_types->get();
    }

    public function getByid($account_id)
    {
        return $this->model_account->find($account_id);
    }
    public function save($obj)
    {
        if (isset($obj['account_id']) && $obj['account_id'] > 0) {
            $this->model_account->update($obj, $obj['account_id']);
            $saved_obj = $this->model_account->find($obj['account_id']);
        }

        if (!$saved_obj)
            return false;

        return $saved_obj;
    }
    public function delete($account_id)
    {
        return $this->model_account->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $account_id);
    }

    public function getAllParent()
    {
        if (getRoleName() != RoleNames::SUPERADMIN) {
            return $this->model_account->getModel()::with($this->with)
                ->where('business_id', Auth::user()->business_id)
                ->whereNull('parent_account_id')
                ->where('is_deleted', 0)
                ->get();
        }
        return $this->model_account->getModel()::with($this->with)
            ->whereNull('business_id')
            ->whereNull('parent_account_id')
            ->where('is_deleted', 0)
            ->get();
    }

    public function getAllChild()
    {
        if (getRoleName() != RoleNames::SUPERADMIN) {
            return $this->model_account->getModel()::with($this->with)
                ->where('business_id', Auth::user()->business_id)
                ->whereNotNull('parent_account_id')
                ->where('is_deleted', 0)
                ->get();
        }
        return $this->model_account->getModel()::with($this->with)
            ->whereNull('business_id')
            ->whereNotNull('parent_account_id')
            ->where('is_deleted', 0)
            ->get();
    }
    public function getParentByBusiness($business_id)
    {
        return $this->model_account->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->whereNull('parent_account_id')
            ->where('is_deleted', 0)
            ->get();
    }

    public function getParentByAccountType($account_type_id)
    {
        return $this->model_account->getModel()::with($this->with)
            ->where('account_type_id', $account_type_id)
            ->whereNull('parent_account_id')
            ->where('is_deleted', 0)
            ->get();
    }

    public function getParentByAccountSubType($account_sub_type_id)
    {
        return $this->model_account->getModel()::with($this->with)
            ->where('account_sub_type_id', $account_sub_type_id)
            ->whereNull('parent_account_id')
            ->where('is_deleted', 0)
            ->get();
    }

    //child
    public function getChildByBusiness($business_id)
    {
        return $this->model_account->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->whereNotNull('parent_account_id')
            ->where('is_deleted', 0)
            ->get();
    }

    public function getChildByAccountType($account_type_id)
    {
        return $this->model_account->getModel()::with($this->with)
            ->where('account_type_id', $account_type_id)
            ->whereNotNull('parent_account_id')
            ->where('is_deleted', 0)
            ->get();
    }

    public function getChildByAccountSubType($account_sub_type_id)
    {
        return $this->model_account->getModel()::with($this->with)
            ->where('account_sub_type_id', $account_sub_type_id)
            ->whereNotNull('parent_account_id')
            ->where('is_deleted', 0)
            ->get();
    }
}
