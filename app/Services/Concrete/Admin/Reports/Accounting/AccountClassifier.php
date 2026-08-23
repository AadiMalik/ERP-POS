<?php

namespace App\Services\Concrete\Admin\Reports\Accounting;

use App\Enums\AccountSubTypes;
use App\Enums\AccountTypes;
use App\Models\Account;
use App\Models\AccountingSetting;

/**
 * Central place that maps Chart of Accounts entries onto the accounting
 * concepts (normal balance side, P&L bucket, Balance Sheet bucket, cash/bank,
 * tax) that no column in the schema stores explicitly. Every accounting
 * report composes this instead of re-deriving the mapping locally.
 *
 * Classification is keyed by `account_types.code` / `account_sub_types.code`
 * (see AccountTypes::CODES / AccountSubTypes::CODES), never by `.name` -
 * `name` is a free-text label a Business Admin can rename via the Account
 * Type/Sub Type CRUD screens at any time, while `code` is the stable
 * identifier assigned when a business's defaults are seeded/reset and never
 * changes, so a renamed category can never cause its accounts to silently
 * disappear from a report.
 */
class AccountClassifier
{
    protected const DEBIT_NORMAL_TYPE_CODES = [
        AccountTypes::CODES[AccountTypes::ASSETS],
        AccountTypes::CODES[AccountTypes::EXPENSES],
    ];

    protected const PL_REVENUE = [
        AccountSubTypes::CODES[AccountSubTypes::OPERATING_REVENUE],
        AccountSubTypes::CODES[AccountSubTypes::SALES_REVENUE],
        AccountSubTypes::CODES[AccountSubTypes::SERVICE_REVENUE],
    ];
    protected const PL_OTHER_INCOME = [AccountSubTypes::CODES[AccountSubTypes::OTHER_REVENUE]];
    protected const PL_COST_OF_REVENUE = [AccountSubTypes::CODES[AccountSubTypes::COST_OF_GOODS_SOLD]];
    protected const PL_DIRECT_EXPENSE = [AccountSubTypes::CODES[AccountSubTypes::DIRECT_EXPENSES]];
    protected const PL_OPERATING_EXPENSE = [
        AccountSubTypes::CODES[AccountSubTypes::ADMINISTRATIVE_EXPENSES],
        AccountSubTypes::CODES[AccountSubTypes::SELLING_DISTRIBUTION_EXPENSES],
        AccountSubTypes::CODES[AccountSubTypes::FINANCIAL_EXPENSES],
        AccountSubTypes::CODES[AccountSubTypes::PAYROLL_EXPENSES],
        AccountSubTypes::CODES[AccountSubTypes::DEPRECIATION_AMORTIZATION],
        AccountSubTypes::CODES[AccountSubTypes::TAX_EXPENSES],
    ];
    protected const PL_OTHER_EXPENSE = [AccountSubTypes::CODES[AccountSubTypes::OTHER_EXPENSES]];

    protected const BS_CURRENT_ASSET = [
        AccountSubTypes::CODES[AccountSubTypes::CURRENT_ASSETS],
        AccountSubTypes::CODES[AccountSubTypes::CASH_CASH_EQUIVALENTS],
        AccountSubTypes::CODES[AccountSubTypes::ACCOUNTS_RECEIVABLE],
        AccountSubTypes::CODES[AccountSubTypes::INVENTORY],
        AccountSubTypes::CODES[AccountSubTypes::PREPAID_EXPENSES],
        AccountSubTypes::CODES[AccountSubTypes::ADVANCES_DEPOSITS],
        AccountSubTypes::CODES[AccountSubTypes::INVESTMENTS],
    ];
    protected const BS_FIXED_ASSET = [
        AccountSubTypes::CODES[AccountSubTypes::FIXED_ASSETS],
        AccountSubTypes::CODES[AccountSubTypes::ACCUMULATED_DEPRECIATION],
        AccountSubTypes::CODES[AccountSubTypes::INTANGIBLE_ASSETS],
    ];
    protected const BS_CURRENT_LIABILITY = [
        AccountSubTypes::CODES[AccountSubTypes::CURRENT_LIABILITIES],
        AccountSubTypes::CODES[AccountSubTypes::ACCOUNTS_PAYABLE],
        AccountSubTypes::CODES[AccountSubTypes::ACCRUED_LIABILITIES],
        AccountSubTypes::CODES[AccountSubTypes::TAXES_PAYABLE],
        AccountSubTypes::CODES[AccountSubTypes::UNEARNED_REVENUE],
        AccountSubTypes::CODES[AccountSubTypes::OTHER_LIABILITIES],
    ];
    protected const BS_LONG_TERM_LIABILITY = [AccountSubTypes::CODES[AccountSubTypes::LOANS_BORROWINGS]];

    public function isDebitNormal(?string $accountTypeCode): bool
    {
        return in_array($accountTypeCode, self::DEBIT_NORMAL_TYPE_CODES, true);
    }

    /**
     * Splits a raw signed balance into the standard debit/credit-column
     * ledger presentation: whichever side the net balance actually sits on
     * is shown, the other is zero, plus a Dr/Cr label relative to the
     * account's own normal side.
     */
    public function toBalance(float $debit, float $credit, bool $debitNormal, ?float $rawBalance = null): array
    {
        $net = $rawBalance ?? ($debitNormal ? ($debit - $credit) : ($credit - $debit));

        return [
            'debit'   => $debit,
            'credit'  => $credit,
            'balance' => round(abs($net), 2),
            'type'    => $net > 0 ? ($debitNormal ? 'Dr' : 'Cr') : ($net < 0 ? ($debitNormal ? 'Cr' : 'Dr') : ''),
            'raw'     => $net,
        ];
    }

    /**
     * Splits a raw (debit - credit) figure into Trial Balance's debit/credit
     * columns purely by which side the number actually falls on - independent
     * of the account's normal side, since a Trial Balance shows the actual
     * position, not the expected one.
     */
    public function splitByNet(float $rawNet): array
    {
        return [
            'debit'  => $rawNet > 0 ? round($rawNet, 2) : 0,
            'credit' => $rawNet < 0 ? round(abs($rawNet), 2) : 0,
        ];
    }

    public function plBucket(?string $subTypeCode): ?string
    {
        if (in_array($subTypeCode, self::PL_REVENUE, true)) {
            return 'revenue';
        }
        if (in_array($subTypeCode, self::PL_OTHER_INCOME, true)) {
            return 'other_income';
        }
        if (in_array($subTypeCode, self::PL_COST_OF_REVENUE, true)) {
            return 'cost_of_revenue';
        }
        if (in_array($subTypeCode, self::PL_DIRECT_EXPENSE, true)) {
            return 'direct_expense';
        }
        if (in_array($subTypeCode, self::PL_OPERATING_EXPENSE, true)) {
            return 'operating_expense';
        }
        if (in_array($subTypeCode, self::PL_OTHER_EXPENSE, true)) {
            return 'other_expense';
        }

        return null;
    }

    public function bsBucket(?string $typeCode, ?string $subTypeCode): string
    {
        if (in_array($subTypeCode, self::BS_CURRENT_ASSET, true)) {
            return 'current_asset';
        }
        if (in_array($subTypeCode, self::BS_FIXED_ASSET, true)) {
            return 'fixed_asset';
        }
        if (in_array($subTypeCode, self::BS_CURRENT_LIABILITY, true)) {
            return 'current_liability';
        }
        if (in_array($subTypeCode, self::BS_LONG_TERM_LIABILITY, true)) {
            return 'long_term_liability';
        }
        if ($typeCode === AccountTypes::CODES[AccountTypes::ASSETS]) {
            return 'other_asset';
        }
        if ($typeCode === AccountTypes::CODES[AccountTypes::LIABILITIES]) {
            return 'other_liability';
        }

        return 'equity';
    }

    public function isCashOrBank(Account $account, ?AccountingSetting $settings): bool
    {
        if (optional($account->accountSubType)->code === AccountSubTypes::CODES[AccountSubTypes::CASH_CASH_EQUIVALENTS]) {
            return true;
        }

        if (!$settings) {
            return false;
        }

        return $account->account_id === $settings->default_cash_account_id
            || $account->account_id === $settings->default_bank_account_id;
    }

    public function isTaxAccount(Account $account, ?AccountingSetting $settings): bool
    {
        if (optional($account->accountSubType)->code === AccountSubTypes::CODES[AccountSubTypes::TAXES_PAYABLE]) {
            return true;
        }

        if ($settings && (
            $account->account_id === $settings->default_tax_account_id
            || $account->account_id === $settings->default_withholding_tax_account_id
        )) {
            return true;
        }

        // Last-resort heuristic for a custom account that was never given a
        // sub-type at all (so the stable code check above can't apply) -
        // deliberately kept as a fallback, not the primary classification
        // mechanism.
        return stripos((string) $account->name, 'tax') !== false;
    }
}
