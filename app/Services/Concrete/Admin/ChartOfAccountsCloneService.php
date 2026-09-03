<?php

namespace App\Services\Concrete\Admin;

use App\Models\Account;
use App\Models\AccountSubType;
use App\Models\AccountType;
use Illuminate\Support\Facades\Auth;

class ChartOfAccountsCloneService
{
    /**
     * Clones the global Chart of Accounts template (business_id = NULL,
     * maintained by Super Admin) into a newly registered business, preserving
     * the full hierarchy: Account Type -> Account Sub Type -> Level 1 (root)
     * Account -> Level 2/3 (child/grandchild) Accounts, to any nesting depth.
     *
     * Idempotent: if this business already has a type/sub-type/account
     * matching a template row's natural key (name for types & sub-types,
     * code for accounts), that existing row is reused instead of cloning a
     * duplicate - safe to call again for a business that was already
     * provisioned.
     *
     * @return array<string, string> Template account_id => new business account_id,
     *         covering every Account level. Used by AccountingSettingCloneService
     *         to remap the system-level default account fields onto this
     *         business's own cloned accounts.
     */
    public function cloneTemplateToBusiness(string $business_id): array
    {
        $createdby_id = Auth::id();
        $now = now();

        $accountTypeIdMap = $this->cloneAccountTypes($business_id, $createdby_id, $now);
        $accountSubTypeIdMap = $this->cloneAccountSubTypes($business_id, $createdby_id, $now, $accountTypeIdMap);
        $parentAccountIdMap = $this->cloneParentAccounts($business_id, $createdby_id, $now, $accountTypeIdMap, $accountSubTypeIdMap);
        $childAccountIdMap = $this->cloneChildAccounts($business_id, $createdby_id, $now, $accountTypeIdMap, $accountSubTypeIdMap, $parentAccountIdMap);

        return $parentAccountIdMap + $childAccountIdMap;
    }

    private function cloneAccountTypes(string $business_id, $createdby_id, $now): array
    {
        $map = [];

        $existing = AccountType::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get()
            ->keyBy('name');

        AccountType::whereNull('business_id')
            ->where('is_deleted', 0)
            ->get()
            ->each(function (AccountType $template) use ($business_id, $createdby_id, $now, $existing, &$map) {
                $clone = $existing->get($template->name);

                if (!$clone) {
                    $clone = new AccountType();
                    $clone->account_type_id = generateUuid();
                    $clone->business_id = $business_id;
                    $clone->name = $template->name;
                    $clone->code = $template->code;
                    $clone->description = $template->description;
                    $clone->is_deleted = 0;
                    $clone->createdby_id = $createdby_id;
                    $clone->date_created = $now;
                    $clone->save();
                }

                $map[$template->account_type_id] = $clone->account_type_id;
            });

        return $map;
    }

    private function cloneAccountSubTypes(string $business_id, $createdby_id, $now, array $accountTypeIdMap): array
    {
        $map = [];

        $existing = AccountSubType::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get()
            ->keyBy('name');

        AccountSubType::whereNull('business_id')
            ->where('is_deleted', 0)
            ->get()
            ->each(function (AccountSubType $template) use ($business_id, $createdby_id, $now, $accountTypeIdMap, $existing, &$map) {
                if (!isset($accountTypeIdMap[$template->account_type_id])) {
                    return;
                }

                $clone = $existing->get($template->name);

                if (!$clone) {
                    $clone = new AccountSubType();
                    $clone->account_sub_type_id = generateUuid();
                    $clone->business_id = $business_id;
                    $clone->account_type_id = $accountTypeIdMap[$template->account_type_id];
                    $clone->name = $template->name;
                    $clone->code = $template->code;
                    $clone->description = $template->description;
                    $clone->is_deleted = 0;
                    $clone->createdby_id = $createdby_id;
                    $clone->date_created = $now;
                    $clone->save();
                }

                $map[$template->account_sub_type_id] = $clone->account_sub_type_id;
            });

        return $map;
    }

    private function cloneParentAccounts(string $business_id, $createdby_id, $now, array $accountTypeIdMap, array $accountSubTypeIdMap): array
    {
        $map = [];

        $existing = Account::where('business_id', $business_id)
            ->whereNull('parent_account_id')
            ->where('is_deleted', 0)
            ->get()
            ->keyBy('code');

        Account::whereNull('business_id')
            ->whereNull('parent_account_id')
            ->where('is_deleted', 0)
            ->get()
            ->each(function (Account $template) use ($business_id, $createdby_id, $now, $accountTypeIdMap, $accountSubTypeIdMap, $existing, &$map) {
                if (!isset($accountTypeIdMap[$template->account_type_id]) || !isset($accountSubTypeIdMap[$template->account_sub_type_id])) {
                    return;
                }

                $clone = $existing->get($template->code)
                    ?? $this->cloneAccount($template, $business_id, $createdby_id, $now, $accountTypeIdMap, $accountSubTypeIdMap, null);

                $map[$template->account_id] = $clone->account_id;
            });

        return $map;
    }

    /**
     * Clones every template account below Level 1, regardless of how many
     * levels deep (Level 2, Level 3, ...). Runs repeated passes over the
     * still-unmapped accounts so a Level 3 grandchild - whose parent is a
     * Level 2 account only mapped in a prior pass - is cloned once its
     * parent mapping becomes available.
     */
    private function cloneChildAccounts(string $business_id, $createdby_id, $now, array $accountTypeIdMap, array $accountSubTypeIdMap, array $parentAccountIdMap): array
    {
        $map = [];

        $existing = Account::where('business_id', $business_id)
            ->whereNotNull('parent_account_id')
            ->where('is_deleted', 0)
            ->get()
            ->keyBy('code');

        $pending = Account::whereNull('business_id')
            ->whereNotNull('parent_account_id')
            ->where('is_deleted', 0)
            ->get()
            ->keyBy('account_id');

        do {
            $clonedThisPass = false;

            foreach ($pending as $accountId => $template) {
                if (!isset($parentAccountIdMap[$template->parent_account_id])) {
                    continue;
                }

                if (
                    !isset($accountTypeIdMap[$template->account_type_id]) ||
                    !isset($accountSubTypeIdMap[$template->account_sub_type_id])
                ) {
                    $pending->forget($accountId);
                    continue;
                }

                $clone = $existing->get($template->code)
                    ?? $this->cloneAccount(
                        $template,
                        $business_id,
                        $createdby_id,
                        $now,
                        $accountTypeIdMap,
                        $accountSubTypeIdMap,
                        $parentAccountIdMap[$template->parent_account_id]
                    );

                $map[$template->account_id] = $clone->account_id;
                $parentAccountIdMap[$template->account_id] = $clone->account_id;
                $pending->forget($accountId);
                $clonedThisPass = true;
            }
        } while ($clonedThisPass && $pending->isNotEmpty());

        return $map;
    }

    private function cloneAccount(Account $template, string $business_id, $createdby_id, $now, array $accountTypeIdMap, array $accountSubTypeIdMap, ?string $parent_account_id): Account
    {
        $clone = new Account();
        $clone->account_id = generateUuid();
        $clone->business_id = $business_id;
        $clone->account_type_id = $accountTypeIdMap[$template->account_type_id];
        $clone->account_sub_type_id = $accountSubTypeIdMap[$template->account_sub_type_id];
        $clone->parent_account_id = $parent_account_id;
        $clone->name = $template->name;
        $clone->code = $template->code;
        $clone->description = $template->description;
        $clone->status = $template->status;
        $clone->is_deleted = 0;
        $clone->createdby_id = $createdby_id;
        $clone->date_created = $now;
        $clone->save();

        return $clone;
    }
}
