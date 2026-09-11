<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * journal_entries.source_type is a DB-level ENUM. Add Complimentary Order /
 * Complimentary Return so a full complimentary sale (or its return) can be
 * identified separately from a normal POS Sale while still using the same
 * Sale Voucher journal category.
 */
return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE journal_entries MODIFY COLUMN source_type ENUM(
            'Manual',
            'Sale', 'Sale Return', 'Sale Order', 'Sale Quotation', 'POS Sale', 'POS Return', 'Delivery',
            'Complimentary Order', 'Complimentary Return',
            'Purchase', 'Purchase Return', 'Purchase Order', 'Purchase Quotation', 'Goods Receipt',
            'Service Purchase', 'Service Purchase Return', 'Service Sale', 'Service Sale Return',
            'Stock Adjustment', 'Stock Transfer', 'Opening Stock', 'Inventory Count', 'Stock Loss', 'Inventory Cost Adjustment',
            'Production', 'Production Consumption', 'Production Finished Goods',
            'Journal Voucher', 'Payment Voucher', 'Receipt Voucher', 'Contra Voucher',
            'Opening Balance', 'Closing Balance', 'Year End Closing', 'Expense', 'Income',
            'Bank Transfer', 'Bank Charge', 'Interest',
            'Customer Payment', 'Supplier Payment', 'Customer Receipt', 'Supplier Receipt',
            'Bank Deposit', 'Bank Withdrawal',
            'Loan', 'Loan Repayment', 'Loan Disbursement',
            'Asset Acquisition', 'Asset Depreciation', 'Asset Transfer', 'Asset Disposal',
            'Payroll', 'Salary', 'Advance Salary', 'Employee Loan', 'Employee Loan Recovery', 'Bonus', 'Overtime',
            'Customer Advance', 'Supplier Advance', 'Customer Refund', 'Supplier Refund',
            'Adjustment', 'Migration', 'System'
        ) DEFAULT 'Manual'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE journal_entries MODIFY COLUMN source_type ENUM(
            'Manual',
            'Sale', 'Sale Return', 'Sale Order', 'Sale Quotation', 'POS Sale', 'POS Return', 'Delivery',
            'Purchase', 'Purchase Return', 'Purchase Order', 'Purchase Quotation', 'Goods Receipt',
            'Service Purchase', 'Service Purchase Return', 'Service Sale', 'Service Sale Return',
            'Stock Adjustment', 'Stock Transfer', 'Opening Stock', 'Inventory Count', 'Stock Loss', 'Inventory Cost Adjustment',
            'Production', 'Production Consumption', 'Production Finished Goods',
            'Journal Voucher', 'Payment Voucher', 'Receipt Voucher', 'Contra Voucher',
            'Opening Balance', 'Closing Balance', 'Year End Closing', 'Expense', 'Income',
            'Bank Transfer', 'Bank Charge', 'Interest',
            'Customer Payment', 'Supplier Payment', 'Customer Receipt', 'Supplier Receipt',
            'Bank Deposit', 'Bank Withdrawal',
            'Loan', 'Loan Repayment', 'Loan Disbursement',
            'Asset Acquisition', 'Asset Depreciation', 'Asset Transfer', 'Asset Disposal',
            'Payroll', 'Salary', 'Advance Salary', 'Employee Loan', 'Employee Loan Recovery', 'Bonus', 'Overtime',
            'Customer Advance', 'Supplier Advance', 'Customer Refund', 'Supplier Refund',
            'Adjustment', 'Migration', 'System'
        ) DEFAULT 'Manual'");
    }
};
