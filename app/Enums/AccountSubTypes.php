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
}
