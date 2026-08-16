<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Repository\Repository;
use Yajra\DataTables\DataTables;
use App\Enums\RoleNames;
use App\Enums\AccountTypes;
use App\Enums\Status;
use App\Models\Account;
use App\Models\AccountSubType;
use App\Models\AccountType;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    /**
     * @param string|null|false $business_id false (default) = role-scoped view
     *        (own business, or every business for Super Admin); an explicit
     *        value (including null, for the global template) filters to that
     *        business only - used by Super Admin's template/business switcher
     *        on the COA tree page.
     */
    public function getData($business_id = false)
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
        ])->where('is_deleted', 0)
            ->orderBy('code', 'asc');

        if ($business_id !== false) {
            $data->where('business_id', $business_id);
        } else {
            $data = applyRoleScope($data, $allow_roles);
        }

        return $data->get();
    }

    public function getByid($account_id)
    {
        return $this->model_account->find($account_id);
    }
    public function save($obj)
    {
        $this->validateHierarchy($obj);

        return DB::transaction(function () use ($obj) {
            $accountModel = $this->model_account->getModel();
            $existing = !empty($obj['account_id']) ? $accountModel::where('account_id', $obj['account_id'])->lockForUpdate()->first() : null;

            // Level 1 accounts keep the manually entered code as-is. Level 2/3
            // codes are always derived from the parent - on create, or on
            // re-parenting - so a submitted code value can never be forged or
            // left stale; editing without changing the parent keeps the
            // existing code untouched.
            if (!empty($obj['parent_account_id'])) {
                if ($existing && $existing->parent_account_id == $obj['parent_account_id']) {
                    $obj['code'] = $existing->code;
                } else {
                    $obj['code'] = $this->nextChildCode($obj['parent_account_id'], $obj['account_id'] ?? null, true);
                }
            }

            if ($existing) {
                $obj['updatedby_id'] = Auth::user()->id;
                $obj['date_updated'] = now();
                $this->model_account->update($obj, $obj['account_id']);
                return $this->model_account->find($obj['account_id']);
            }

            $obj['account_id'] = generateUuid();
            $obj['createdby_id'] = Auth::user()->id;
            $obj['date_created'] = now();
            return $this->model_account->create($obj);
        });
    }

    /**
     * Returns the next auto-generated code for a child of $parentAccountId,
     * i.e. the parent's own code with a zero-padded 3-digit sequence appended
     * (1130 -> 1130-001, 1130-001 -> 1130-001-001). $excludeAccountId omits
     * that account from the sibling scan, so re-saving an existing child
     * under the same parent doesn't count itself when picking the sequence.
     */
    private function nextChildCode($parentAccountId, $excludeAccountId = null, bool $lock = false)
    {
        $accountModel = $this->model_account->getModel();

        $parentQuery = $accountModel::where('account_id', $parentAccountId)->where('is_deleted', 0);
        if ($lock) {
            $parentQuery->lockForUpdate();
        }
        $parent = $parentQuery->first();

        if (!$parent) {
            throw new Exception('The selected parent account does not exist.');
        }

        $siblingQuery = $accountModel::where('parent_account_id', $parentAccountId)->where('is_deleted', 0);
        if ($excludeAccountId) {
            $siblingQuery->where('account_id', '!=', $excludeAccountId);
        }
        if ($lock) {
            $siblingQuery->lockForUpdate();
        }

        $maxSequence = 0;
        foreach ($siblingQuery->pluck('code') as $siblingCode) {
            $suffix = Str::afterLast($siblingCode, '-');
            if (ctype_digit($suffix)) {
                $maxSequence = max($maxSequence, (int) $suffix);
            }
        }

        $nextSequence = $maxSequence + 1;
        if ($nextSequence > 999) {
            throw new Exception('Maximum number of child accounts (999) reached under this parent account.');
        }

        return $parent->code . '-' . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Read-only preview of the code a new child would receive under
     * $parentAccountId, used by the "Add Child" form to show the code before
     * saving. save() recomputes it authoritatively (with row locking) at
     * write time, so this preview can never itself cause a duplicate.
     */
    public function previewNextChildCode($parentAccountId)
    {
        return $this->nextChildCode($parentAccountId);
    }

    /**
     * Enforces the 3-level COA account hierarchy (Level 1 root -> Level 2
     * child -> Level 3 grandchild, each scoped to a single Account Type /
     * Account Sub Type): Level 1 accounts require Type/Sub Type, and a new
     * account may only be nested under a parent that isn't already a Level 3
     * account (which would create an unsupported 4th level).
     */
    private function validateHierarchy($obj)
    {
        $accountModel = $this->model_account->getModel();
        $accountId = $obj['account_id'] ?? null;
        $parentAccountId = $obj['parent_account_id'] ?? null;

        if (empty($parentAccountId)) {
            if (empty($obj['account_type_id']) || empty($obj['account_sub_type_id'])) {
                throw new Exception('Account Type and Account Sub Type are required.');
            }

            $subType = AccountSubType::where('account_sub_type_id', $obj['account_sub_type_id'])
                ->where('is_deleted', 0)
                ->first();

            if (!$subType || $subType->account_type_id != $obj['account_type_id']) {
                throw new Exception('The selected Account Sub Type does not belong to the selected Account Type.');
            }

            return;
        }

        $parent = $accountModel::where('account_id', $parentAccountId)
            ->where('is_deleted', 0)
            ->first();

        if (!$parent) {
            throw new Exception('The selected parent account does not exist.');
        }

        if ($accountId && $parent->account_id == $accountId) {
            throw new Exception('An account cannot be its own parent.');
        }

        if (!empty($parent->parent_account_id)) {
            $grandparent = $accountModel::where('account_id', $parent->parent_account_id)
                ->where('is_deleted', 0)
                ->first();

            if ($grandparent && !empty($grandparent->parent_account_id)) {
                throw new Exception('Cannot create an account below Level 3 - the selected parent account is already at the maximum depth.');
            }
        }

        if (!empty($obj['account_type_id']) && $obj['account_type_id'] != $parent->account_type_id) {
            throw new Exception('Account Type must match the selected parent account.');
        }

        if (!empty($obj['account_sub_type_id']) && $obj['account_sub_type_id'] != $parent->account_sub_type_id) {
            throw new Exception('Account Sub Type must match the selected parent account.');
        }

        if ($accountId) {
            $current = $accountModel::where('account_id', $accountId)->first();
            $isReparenting = $current && $current->parent_account_id != $parentAccountId;

            if ($isReparenting) {
                $hasChildren = $accountModel::where('parent_account_id', $accountId)
                    ->where('is_deleted', 0)
                    ->exists();

                if ($hasChildren) {
                    throw new Exception('This account already has its own child account(s) and cannot be moved under another parent account.');
                }
            }
        }
    }

    public function status($account_id)
    {
        return $this->model_account->update([
            'status' => ($this->model_account->find($account_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now()
        ], $account_id);
    }
    public function delete($account_id)
    {
        $account = $this->model_account->find($account_id);

        if (!$account) {
            throw new Exception('Account not found.');
        }

        // Check Child Accounts
        $childCount = $this->model_account->getModel()::where('parent_account_id', $account_id)
            ->where('is_deleted', 0)
            ->count();

        if ($childCount > 0) {
            throw new Exception(
                'This account cannot be deleted because it has child account(s). Please delete the child account(s) first.'
            );
        }

        return $this->model_account->update([
            'is_deleted'   => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
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

    /**
     * Eligible "Parent Account" choices for the Add/Edit Child form - Level 1
     * and Level 2 accounts (a Level 2 selection creates a Level 3 grandchild).
     * Level 3 accounts are excluded since they can't have children of their own.
     */
    public function getParentByAccountType($account_type_id)
    {
        return $this->model_account->getModel()::with($this->with)
            ->where('account_type_id', $account_type_id)
            ->where('is_deleted', 0)
            ->where(function ($q) {
                $q->whereNull('parent_account_id')
                    ->orWhereHas('parentAccount', function ($q2) {
                        $q2->whereNull('parent_account_id');
                    });
            })
            ->get();
    }

    public function getParentByAccountSubType($account_sub_type_id)
    {
        return $this->model_account->getModel()::with($this->with)
            ->where('account_sub_type_id', $account_sub_type_id)
            ->where('is_deleted', 0)
            ->where(function ($q) {
                $q->whereNull('parent_account_id')
                    ->orWhereHas('parentAccount', function ($q2) {
                        $q2->whereNull('parent_account_id');
                    });
            })
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

    /**
     * All active Chart of Accounts entries for a business, ordered by code -
     * used to populate the Account picker filter on the accounting reports.
     */
    public function getAllActive(?string $business_id = null)
    {
        $query = $this->model_account->getModel()::with(['accountType', 'accountSubType'])
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            // Only Level 2/3 (Child) accounts are selectable - Account Type,
            // Account Sub Type, and Level 1 (root) accounts never appear
            // in account-picker dropdowns.
            ->whereNotNull('parent_account_id');

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }

        return $query->orderBy('code')->get();
    }
}
