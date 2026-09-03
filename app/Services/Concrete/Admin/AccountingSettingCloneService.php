<?php

namespace App\Services\Concrete\Admin;

use App\Models\AccountingSetting;
use Illuminate\Support\Facades\Auth;

class AccountingSettingCloneService
{
    /**
     * Every default_*_account_id field on AccountingSetting - covers payment
     * accounts (cash/bank), tax accounts, receivable/payable accounts,
     * inventory accounts, sales/purchase accounts, expense accounts, and the
     * rest of the automated-posting mappings. Each one must end up pointing
     * at the new business's own cloned COA account - never a global/template
     * id or another business's account.
     */
    private const ACCOUNT_FIELDS = [
        'default_cash_account_id',
        'default_bank_account_id',
        'default_discount_account_id',
        'default_tax_account_id',
        'default_revenue_account_id',
        'default_purchase_account_id',
        'default_expense_account_id',
        'default_supplier_account_id',
        'default_customer_account_id',
        'default_carriage_account_id',
        'default_round_off_account_id',
        'default_purchase_return_account_id',
        'default_sale_account_id',
        'default_sale_return_account_id',
        'default_inventory_account_id',
        'default_cogs_account_id',
        'default_opening_stock_account_id',
        'default_stock_adjustment_account_id',
        'default_withholding_tax_account_id',
        'default_fixed_asset_account_id',
        'default_accumulated_depreciation_account_id',
        'default_depreciation_expense_account_id',
        'default_gain_on_asset_disposal_account_id',
        'default_loss_on_asset_disposal_account_id',
    ];

    /**
     * Non-account financial settings copied as-is from the system-level
     * template.
     */
    private const SETTING_FIELDS = [
        'enable_accounting',
        'manual_payment_account_selection',
        'currency',
        'currency_symbol',
        'currency_position',
        'decimal_points',
        'aging_basis',
    ];

    /**
     * Initializes a newly registered business's Accounting Settings from the
     * system-level template (business_id = NULL, maintained by Super Admin
     * through the same Settings > Accounting screen), remapping every
     * default account reference onto the accounts
     * ChartOfAccountsCloneService just cloned for this business.
     *
     * Idempotent: if this business already has an AccountingSetting row, it
     * is reused rather than duplicated, and only fields the accountant/admin
     * hasn't already configured are filled in from the template - a later
     * manual remapping in Settings > Accounting is never overwritten.
     *
     * @param array<string, string> $accountIdMap Template account_id => new
     *        business account_id, as returned by
     *        ChartOfAccountsCloneService::cloneTemplateToBusiness().
     */
    public function cloneTemplateToBusiness(string $business_id, array $accountIdMap): void
    {
        $template = AccountingSetting::whereNull('business_id')->first();

        $setting = AccountingSetting::where('business_id', $business_id)->first();
        $isNew = !$setting;

        if ($isNew) {
            $setting = new AccountingSetting();
            $setting->business_id = $business_id;
        }

        foreach (self::SETTING_FIELDS as $field) {
            if (!$isNew && $setting->$field !== null) {
                continue;
            }

            if ($template && $template->$field !== null) {
                $setting->$field = $template->$field;
            }
        }

        foreach (self::ACCOUNT_FIELDS as $field) {
            if (!$isNew && $setting->$field !== null) {
                continue;
            }

            $templateAccountId = $template?->$field;

            // Only ever point at an account cloned for THIS business - if
            // the template account wasn't cloned (or no template is
            // configured), leave it null rather than falling back to the
            // template's own (global) account id.
            $setting->$field = ($templateAccountId && isset($accountIdMap[$templateAccountId]))
                ? $accountIdMap[$templateAccountId]
                : null;
        }

        if ($isNew) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        } else {
            $setting->updatedby_id = Auth::id();
        }

        $setting->date_updated = now();
        $setting->save();
    }
}
