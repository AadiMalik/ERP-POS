<?php

namespace App\Enums;

class  AccountSubTypes
{
    //ASSETS
    const CURRENT_ASSETS = 'Current Assets';
    const CASH_CASH_EQUIVALENTS = 'Cash & Cash Equivalents';
    const ACCOUNTS_RECEIVABLE = 'Accounts Receivable';
    const INVENTORY = 'Inventory';
    const PREPAID_EXPENSES = 'Prepaid Expenses';
    const ADVANCES_DEPOSITS = 'Advances & Deposits';
    const INVESTMENTS = 'Investments';
    const FIXED_ASSETS = 'Fixed Assets';
    const ACCUMULATED_DEPRECIATION = 'Accumulated Depreciation';
    const INTANGIBLE_ASSETS = 'Intangible Assets';

    //LIABILITIES
    const CURRENT_LIABILITIES = 'Current Liabilities';
    const ACCOUNTS_PAYABLE = 'Accounts Payable';
    const ACCRUED_LIABILITIES = 'Accrued Liabilities';
    const TAXES_PAYABLE = 'Taxes Payable';
    const LOANS_BORROWINGS = 'Loans & Borrowings';
    const UNEARNED_REVENUE = 'Unearned Revenue';
    const OTHER_LIABILITIES = 'Other Liabilities';

    // EQUITY
    const CAPITAL = 'Capital';
    const RETAINED_EARNINGS = 'Retained Earnings';
    const CURRENT_YEAR_EARNINGS = 'Current Year Earnings';
    const DRAWINGS = 'Drawings';
    const RESERVES = 'Reserves';

    // REVENUE
    const OPERATING_REVENUE = 'Operating Revenue';
    const SALES_REVENUE = 'Sales Revenue';
    const SERVICE_REVENUE = 'Service Revenue';
    const OTHER_REVENUE = 'Other Revenue';

    //Expenses
    const COST_OF_GOODS_SOLD = 'Cost of Goods Sold';
    const DIRECT_EXPENSES = 'Direct Expenses';
    const ADMINISTRATIVE_EXPENSES = 'Administrative Expenses';
    const SELLING_DISTRIBUTION_EXPENSES = 'Selling & Distribution Expenses';
    const FINANCIAL_EXPENSES = 'Financial Expenses';
    const PAYROLL_EXPENSES = 'Payroll Expenses';
    const DEPRECIATION_AMORTIZATION = 'Depreciation & Amortization';
    const TAX_EXPENSES = 'Tax Expenses';
    const OTHER_EXPENSES = 'Other Expenses';

    /**
     * Stable classification codes, one per default Account Sub Type -
     * matches the codes AccountSubTypeService::resetBusinessAccountSubType()
     * assigns when seeding/resetting a business's defaults. See
     * AccountTypes::CODES for why classification must use these instead of
     * the free-text, renamable `name` column.
     */
    const CODES = [
        self::CURRENT_ASSETS => '1100',
        self::CASH_CASH_EQUIVALENTS => '1110',
        self::ACCOUNTS_RECEIVABLE => '1120',
        self::INVENTORY => '1130',
        self::PREPAID_EXPENSES => '1140',
        self::ADVANCES_DEPOSITS => '1150',
        self::INVESTMENTS => '1160',
        self::FIXED_ASSETS => '1700',
        self::ACCUMULATED_DEPRECIATION => '1800',
        self::INTANGIBLE_ASSETS => '1900',

        self::CURRENT_LIABILITIES => '2100',
        self::ACCOUNTS_PAYABLE => '2200',
        self::ACCRUED_LIABILITIES => '2300',
        self::TAXES_PAYABLE => '2400',
        self::LOANS_BORROWINGS => '2500',
        self::UNEARNED_REVENUE => '2600',
        self::OTHER_LIABILITIES => '2900',

        self::CAPITAL => '3100',
        self::RETAINED_EARNINGS => '3200',
        self::CURRENT_YEAR_EARNINGS => '3300',
        self::DRAWINGS => '3400',
        self::RESERVES => '3500',

        self::OPERATING_REVENUE => '4100',
        self::SALES_REVENUE => '4200',
        self::SERVICE_REVENUE => '4300',
        self::OTHER_REVENUE => '4900',

        self::COST_OF_GOODS_SOLD => '5100',
        self::DIRECT_EXPENSES => '5200',
        self::ADMINISTRATIVE_EXPENSES => '5300',
        self::SELLING_DISTRIBUTION_EXPENSES => '5400',
        self::FINANCIAL_EXPENSES => '5500',
        self::PAYROLL_EXPENSES => '5600',
        self::DEPRECIATION_AMORTIZATION => '5700',
        self::TAX_EXPENSES => '5800',
        self::OTHER_EXPENSES => '5900',
    ];
}
