<?php

namespace App\Services\Concrete\Admin;

class AccountingSetupWizardService
{
    protected ChartOfAccountsCloneService $chart_of_accounts_clone_service;
    protected AccountingSettingCloneService $accounting_setting_clone_service;

    public function __construct(
        ChartOfAccountsCloneService $chart_of_accounts_clone_service,
        AccountingSettingCloneService $accounting_setting_clone_service
    ) {
        $this->chart_of_accounts_clone_service = $chart_of_accounts_clone_service;
        $this->accounting_setting_clone_service = $accounting_setting_clone_service;
    }

    /**
     * Automatically provisions a properly structured accounting environment
     * for a business from the system-level template (Chart of Accounts +
     * Accounting Settings, maintained by Super Admin via Settings >
     * Accounting on their own business_id, which is NULL) - Cash, Bank,
     * Sales, Purchases, Accounts Receivable/Payable, Inventory, COGS,
     * Expenses, and Fixed Asset accounts (Asset, Accumulated Depreciation,
     * Depreciation Expense) are all created and mapped without requiring the
     * business owner to know any accounting. Account ids are never
     * hard-coded - every mapping is resolved dynamically from the accounts
     * cloned for this specific business.
     *
     * Safe to call more than once for the same business: already-cloned
     * accounts/settings are reused rather than duplicated, and any mapping
     * an accountant/admin has since changed in Settings > Accounting is left
     * untouched.
     */
    public function setupForBusiness(string $business_id): void
    {
        $account_id_map = $this->chart_of_accounts_clone_service->cloneTemplateToBusiness($business_id);
        $this->accounting_setting_clone_service->cloneTemplateToBusiness($business_id, $account_id_map);
    }
}
