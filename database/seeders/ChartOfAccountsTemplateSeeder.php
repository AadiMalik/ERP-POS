<?php

namespace Database\Seeders;

use App\Enums\AccountSubTypes;
use App\Enums\AccountTypes;
use App\Models\Account;
use App\Models\AccountingSetting;
use App\Models\AccountSubType;
use App\Models\AccountType;
use App\Services\Concrete\Admin\AccountSubTypeService;
use App\Services\Concrete\Admin\AccountTypeService;
use Illuminate\Database\Seeder;

class ChartOfAccountsTemplateSeeder extends Seeder
{
    /**
     * Seeds the global Chart of Accounts template (business_id = NULL) used by
     * ChartOfAccountsCloneService to populate every newly registered business
     * with the same hierarchy: Account Type -> Account Sub Type -> Level 1
     * (root) Account -> Level 2 (child) Account, using the hyphenated code
     * scheme (Level 1 "111001" -> Level 2 "111001-001").
     */
    public function run()
    {
        // Account Type & Account Sub Type - reuse the existing reset logic,
        // targeted at the global template (business_id = null) instead of
        // the current user.
        app(AccountTypeService::class)->resetBusinessAccountType(null);
        app(AccountSubTypeService::class)->resetBusinessAccountSubType(null);

        // Level 1 (root) accounts & their Level 2 (child) accounts.
        foreach ($this->parentAccounts() as $definition) {
            $accountType = AccountType::whereNull('business_id')
                ->where('name', $definition['account_type'])
                ->where('is_deleted', 0)
                ->first();

            $accountSubType = AccountSubType::whereNull('business_id')
                ->where('account_type_id', $accountType?->account_type_id)
                ->where('name', $definition['account_sub_type'])
                ->where('is_deleted', 0)
                ->first();

            if (!$accountType || !$accountSubType) {
                continue;
            }

            $parent = Account::firstOrNew([
                'business_id' => null,
                'account_sub_type_id' => $accountSubType->account_sub_type_id,
                'parent_account_id' => null,
                'name' => $definition['parent_name'],
            ]);

            $parent->account_id = $parent->account_id ?: generateUuid();
            $parent->account_type_id = $accountType->account_type_id;
            $parent->account_sub_type_id = $accountSubType->account_sub_type_id;
            $parent->code = $definition['parent_code'];
            $parent->description = $definition['parent_description'] ?? null;
            $parent->status = 'active';
            $parent->is_deleted = 0;
            $parent->date_created = $parent->exists ? $parent->date_created : now();
            $parent->date_updated = now();
            $parent->save();

            foreach ($definition['children'] as $child) {
                $childAccount = Account::firstOrNew([
                    'business_id' => null,
                    'parent_account_id' => $parent->account_id,
                    'name' => $child['name'],
                ]);

                $childAccount->account_id = $childAccount->account_id ?: generateUuid();
                $childAccount->account_type_id = $accountType->account_type_id;
                $childAccount->account_sub_type_id = $accountSubType->account_sub_type_id;
                $childAccount->code = $child['code'];
                $childAccount->description = $child['description'] ?? null;
                $childAccount->status = 'active';
                $childAccount->is_deleted = 0;
                $childAccount->date_created = $childAccount->exists ? $childAccount->date_created : now();
                $childAccount->date_updated = now();
                $childAccount->save();
            }
        }

        $this->seedAccountingSettingTemplate();
    }

    /**
     * Seeds the system-level Accounting Settings (business_id = NULL) that
     * AccountingSettingCloneService copies into every newly registered
     * business, remapped onto that business's own cloned accounts. Super
     * Admin can adjust this later via the same Settings > Accounting screen
     * used for a regular business (it operates on their own business_id,
     * which is NULL).
     */
    private function seedAccountingSettingTemplate(): void
    {
        // Maps each default_*_account_id field to the code of the template
        // Level 2 (child) account it should point at - the same accounts
        // created in parentAccounts() above.
        $accountCodeByField = [
            'default_cash_account_id' => '111001-001',
            'default_bank_account_id' => '111002-001',
            'default_discount_account_id' => '530002-001',
            'default_tax_account_id' => '240001-001',
            'default_revenue_account_id' => '420001-001',
            'default_purchase_account_id' => '520001-001',
            'default_expense_account_id' => '530001-001',
            'default_supplier_account_id' => '220001-001',
            'default_customer_account_id' => '112001-001',
            'default_carriage_account_id' => '520003-001',
            'default_round_off_account_id' => '590001-001',
            'default_purchase_return_account_id' => '520002-001',
            'default_sale_account_id' => '420001-001',
            'default_sale_return_account_id' => '420002-001',
            'default_inventory_account_id' => '113001-001',
            'default_cogs_account_id' => '510001-001',
            'default_opening_stock_account_id' => '113002-001',
            'default_stock_adjustment_account_id' => '520004-001',
            'default_withholding_tax_account_id' => '240002-001',
        ];

        $setting = AccountingSetting::firstOrNew(['business_id' => null]);

        foreach ($accountCodeByField as $field => $code) {
            // Don't clobber a value Super Admin has already picked manually.
            if (!empty($setting->$field)) {
                continue;
            }

            $account = Account::whereNull('business_id')
                ->where('code', $code)
                ->where('is_deleted', 0)
                ->first();

            if ($account) {
                $setting->$field = $account->account_id;
            }
        }

        if (!$setting->exists) {
            $setting->enable_accounting = true;
            $setting->manual_payment_account_selection = false;
            $setting->currency = 'USD';
            $setting->currency_symbol = '$';
            $setting->currency_position = 'before';
            $setting->decimal_points = 2;
            $setting->aging_basis = 'due_date';
            $setting->date_created = now();
        }

        $setting->date_updated = now();
        $setting->save();
    }

    /**
     * Level 1 (root) accounts, each with its Level 2 (child) accounts.
     * Every AccountingSetting default_*_account_id field has a matching
     * child account here so a cloned business can select one immediately.
     */
    private function parentAccounts(): array
    {
        return [
            // ASSETS
            [
                'account_type' => AccountTypes::ASSETS,
                'account_sub_type' => AccountSubTypes::CASH_CASH_EQUIVALENTS,
                'parent_code' => '111001',
                'parent_name' => 'Cash in Hand',
                'children' => [
                    ['code' => '111001-001', 'name' => 'Cash - Main Register'],
                ],
            ],
            [
                'account_type' => AccountTypes::ASSETS,
                'account_sub_type' => AccountSubTypes::CASH_CASH_EQUIVALENTS,
                'parent_code' => '111002',
                'parent_name' => 'Bank Accounts',
                'children' => [
                    ['code' => '111002-001', 'name' => 'Bank - Primary Account'],
                ],
            ],
            [
                'account_type' => AccountTypes::ASSETS,
                'account_sub_type' => AccountSubTypes::ACCOUNTS_RECEIVABLE,
                'parent_code' => '112001',
                'parent_name' => 'Trade Receivables',
                'children' => [
                    ['code' => '112001-001', 'name' => 'Accounts Receivable - Trade'],
                ],
            ],
            [
                'account_type' => AccountTypes::ASSETS,
                'account_sub_type' => AccountSubTypes::INVENTORY,
                'parent_code' => '113001',
                'parent_name' => 'Inventory Asset',
                'children' => [
                    ['code' => '113001-001', 'name' => 'Inventory - Trading Stock'],
                ],
            ],
            [
                'account_type' => AccountTypes::ASSETS,
                'account_sub_type' => AccountSubTypes::INVENTORY,
                'parent_code' => '113002',
                'parent_name' => 'Opening Stock',
                'children' => [
                    ['code' => '113002-001', 'name' => 'Opening Stock'],
                ],
            ],
            [
                'account_type' => AccountTypes::ASSETS,
                'account_sub_type' => AccountSubTypes::PREPAID_EXPENSES,
                'parent_code' => '114001',
                'parent_name' => 'Prepaid Expenses',
                'children' => [
                    ['code' => '114001-001', 'name' => 'Prepaid Expenses - General'],
                ],
            ],
            [
                'account_type' => AccountTypes::ASSETS,
                'account_sub_type' => AccountSubTypes::ADVANCES_DEPOSITS,
                'parent_code' => '115001',
                'parent_name' => 'Advances & Deposits',
                'children' => [
                    ['code' => '115001-001', 'name' => 'Employee Advances'],
                ],
            ],
            [
                'account_type' => AccountTypes::ASSETS,
                'account_sub_type' => AccountSubTypes::INVESTMENTS,
                'parent_code' => '116001',
                'parent_name' => 'Investments',
                'children' => [
                    ['code' => '116001-001', 'name' => 'Short Term Investments'],
                ],
            ],
            [
                'account_type' => AccountTypes::ASSETS,
                'account_sub_type' => AccountSubTypes::FIXED_ASSETS,
                'parent_code' => '170001',
                'parent_name' => 'Property, Plant & Equipment',
                'children' => [
                    ['code' => '170001-001', 'name' => 'Furniture & Fixtures'],
                ],
            ],
            [
                'account_type' => AccountTypes::ASSETS,
                'account_sub_type' => AccountSubTypes::ACCUMULATED_DEPRECIATION,
                'parent_code' => '180001',
                'parent_name' => 'Accumulated Depreciation',
                'children' => [
                    ['code' => '180001-001', 'name' => 'Accumulated Depreciation - Fixed Assets'],
                ],
            ],
            [
                'account_type' => AccountTypes::ASSETS,
                'account_sub_type' => AccountSubTypes::INTANGIBLE_ASSETS,
                'parent_code' => '190001',
                'parent_name' => 'Intangible Assets',
                'children' => [
                    ['code' => '190001-001', 'name' => 'Software & Licenses'],
                ],
            ],

            // LIABILITIES
            [
                'account_type' => AccountTypes::LIABILITIES,
                'account_sub_type' => AccountSubTypes::ACCOUNTS_PAYABLE,
                'parent_code' => '220001',
                'parent_name' => 'Trade Payables',
                'children' => [
                    ['code' => '220001-001', 'name' => 'Accounts Payable - Trade'],
                ],
            ],
            [
                'account_type' => AccountTypes::LIABILITIES,
                'account_sub_type' => AccountSubTypes::ACCRUED_LIABILITIES,
                'parent_code' => '230001',
                'parent_name' => 'Accrued Liabilities',
                'children' => [
                    ['code' => '230001-001', 'name' => 'Accrued Expenses'],
                ],
            ],
            [
                'account_type' => AccountTypes::LIABILITIES,
                'account_sub_type' => AccountSubTypes::TAXES_PAYABLE,
                'parent_code' => '240001',
                'parent_name' => 'Sales Tax Payable',
                'children' => [
                    ['code' => '240001-001', 'name' => 'Sales Tax Payable'],
                ],
            ],
            [
                'account_type' => AccountTypes::LIABILITIES,
                'account_sub_type' => AccountSubTypes::TAXES_PAYABLE,
                'parent_code' => '240002',
                'parent_name' => 'Withholding Tax Payable',
                'children' => [
                    ['code' => '240002-001', 'name' => 'Withholding Tax Payable'],
                ],
            ],
            [
                'account_type' => AccountTypes::LIABILITIES,
                'account_sub_type' => AccountSubTypes::LOANS_BORROWINGS,
                'parent_code' => '250001',
                'parent_name' => 'Loans & Borrowings',
                'children' => [
                    ['code' => '250001-001', 'name' => 'Bank Loan'],
                ],
            ],
            [
                'account_type' => AccountTypes::LIABILITIES,
                'account_sub_type' => AccountSubTypes::UNEARNED_REVENUE,
                'parent_code' => '260001',
                'parent_name' => 'Unearned Revenue',
                'children' => [
                    ['code' => '260001-001', 'name' => 'Customer Advances'],
                ],
            ],
            [
                'account_type' => AccountTypes::LIABILITIES,
                'account_sub_type' => AccountSubTypes::OTHER_LIABILITIES,
                'parent_code' => '290001',
                'parent_name' => 'Other Liabilities',
                'children' => [
                    ['code' => '290001-001', 'name' => 'Other Payables'],
                ],
            ],

            // EQUITY
            [
                'account_type' => AccountTypes::EQUITY,
                'account_sub_type' => AccountSubTypes::CAPITAL,
                'parent_code' => '310001',
                'parent_name' => "Owner's Capital",
                'children' => [
                    ['code' => '310001-001', 'name' => "Owner's Capital"],
                ],
            ],
            [
                'account_type' => AccountTypes::EQUITY,
                'account_sub_type' => AccountSubTypes::RETAINED_EARNINGS,
                'parent_code' => '320001',
                'parent_name' => 'Retained Earnings',
                'children' => [
                    ['code' => '320001-001', 'name' => 'Retained Earnings'],
                ],
            ],
            [
                'account_type' => AccountTypes::EQUITY,
                'account_sub_type' => AccountSubTypes::CURRENT_YEAR_EARNINGS,
                'parent_code' => '330001',
                'parent_name' => 'Current Year Earnings',
                'children' => [
                    ['code' => '330001-001', 'name' => 'Current Year Profit / Loss'],
                ],
            ],
            [
                'account_type' => AccountTypes::EQUITY,
                'account_sub_type' => AccountSubTypes::DRAWINGS,
                'parent_code' => '340001',
                'parent_name' => 'Drawings',
                'children' => [
                    ['code' => '340001-001', 'name' => "Owner's Drawings"],
                ],
            ],
            [
                'account_type' => AccountTypes::EQUITY,
                'account_sub_type' => AccountSubTypes::RESERVES,
                'parent_code' => '350001',
                'parent_name' => 'Reserves',
                'children' => [
                    ['code' => '350001-001', 'name' => 'General Reserve'],
                ],
            ],

            // REVENUE
            [
                'account_type' => AccountTypes::REVENUE,
                'account_sub_type' => AccountSubTypes::SALES_REVENUE,
                'parent_code' => '420001',
                'parent_name' => 'Sales Revenue',
                'children' => [
                    ['code' => '420001-001', 'name' => 'Sales Revenue - Trading'],
                ],
            ],
            [
                'account_type' => AccountTypes::REVENUE,
                'account_sub_type' => AccountSubTypes::SALES_REVENUE,
                'parent_code' => '420002',
                'parent_name' => 'Sales Returns & Allowances',
                'children' => [
                    ['code' => '420002-001', 'name' => 'Sales Returns & Allowances'],
                ],
            ],
            [
                'account_type' => AccountTypes::REVENUE,
                'account_sub_type' => AccountSubTypes::SERVICE_REVENUE,
                'parent_code' => '430001',
                'parent_name' => 'Service Revenue',
                'children' => [
                    ['code' => '430001-001', 'name' => 'Service Revenue'],
                ],
            ],
            [
                'account_type' => AccountTypes::REVENUE,
                'account_sub_type' => AccountSubTypes::OTHER_REVENUE,
                'parent_code' => '490001',
                'parent_name' => 'Other Income',
                'children' => [
                    ['code' => '490001-001', 'name' => 'Other Income'],
                ],
            ],

            // EXPENSES
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::COST_OF_GOODS_SOLD,
                'parent_code' => '510001',
                'parent_name' => 'Cost of Goods Sold',
                'children' => [
                    ['code' => '510001-001', 'name' => 'Cost of Goods Sold'],
                ],
            ],
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::DIRECT_EXPENSES,
                'parent_code' => '520001',
                'parent_name' => 'Purchases',
                'children' => [
                    ['code' => '520001-001', 'name' => 'Purchases'],
                ],
            ],
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::DIRECT_EXPENSES,
                'parent_code' => '520002',
                'parent_name' => 'Purchase Returns & Allowances',
                'children' => [
                    ['code' => '520002-001', 'name' => 'Purchase Returns & Allowances'],
                ],
            ],
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::DIRECT_EXPENSES,
                'parent_code' => '520003',
                'parent_name' => 'Carriage & Freight',
                'children' => [
                    ['code' => '520003-001', 'name' => 'Carriage Inward / Freight'],
                ],
            ],
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::DIRECT_EXPENSES,
                'parent_code' => '520004',
                'parent_name' => 'Stock Adjustment',
                'children' => [
                    ['code' => '520004-001', 'name' => 'Stock Adjustment'],
                ],
            ],
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::ADMINISTRATIVE_EXPENSES,
                'parent_code' => '530001',
                'parent_name' => 'Office & Administrative Expenses',
                'children' => [
                    ['code' => '530001-001', 'name' => 'General & Administrative Expenses'],
                ],
            ],
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::ADMINISTRATIVE_EXPENSES,
                'parent_code' => '530002',
                'parent_name' => 'Discount Allowed',
                'children' => [
                    ['code' => '530002-001', 'name' => 'Discount Allowed'],
                ],
            ],
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::SELLING_DISTRIBUTION_EXPENSES,
                'parent_code' => '540001',
                'parent_name' => 'Selling & Distribution Expenses',
                'children' => [
                    ['code' => '540001-001', 'name' => 'Marketing & Advertising'],
                ],
            ],
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::FINANCIAL_EXPENSES,
                'parent_code' => '550001',
                'parent_name' => 'Financial Expenses',
                'children' => [
                    ['code' => '550001-001', 'name' => 'Bank Charges'],
                ],
            ],
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::PAYROLL_EXPENSES,
                'parent_code' => '560001',
                'parent_name' => 'Payroll Expenses',
                'children' => [
                    ['code' => '560001-001', 'name' => 'Salaries & Wages'],
                ],
            ],
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::DEPRECIATION_AMORTIZATION,
                'parent_code' => '570001',
                'parent_name' => 'Depreciation & Amortization',
                'children' => [
                    ['code' => '570001-001', 'name' => 'Depreciation Expense'],
                ],
            ],
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::TAX_EXPENSES,
                'parent_code' => '580001',
                'parent_name' => 'Tax Expenses',
                'children' => [
                    ['code' => '580001-001', 'name' => 'Income Tax Expense'],
                ],
            ],
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::OTHER_EXPENSES,
                'parent_code' => '590001',
                'parent_name' => 'Round Off',
                'children' => [
                    ['code' => '590001-001', 'name' => 'Round Off / Rounding Adjustment'],
                ],
            ],
            [
                'account_type' => AccountTypes::EXPENSES,
                'account_sub_type' => AccountSubTypes::OTHER_EXPENSES,
                'parent_code' => '590002',
                'parent_name' => 'Miscellaneous Expenses',
                'children' => [
                    ['code' => '590002-001', 'name' => 'Miscellaneous Expenses'],
                ],
            ],
        ];
    }
}
