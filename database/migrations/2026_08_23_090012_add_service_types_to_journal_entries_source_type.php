<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `journal_entries.source_type` is a MySQL ENUM (see
     * 2026_07_02_222956_create_journal_entries_table.php) - it must be
     * altered in place to add the Service Management values, or every
     * Service Purchase/Sale (Return) approval fails with "Data truncated for
     * column 'source_type'".
     */
    public function up()
    {
        DB::statement("ALTER TABLE journal_entries MODIFY source_type ENUM(
            'Manual',
            'Sale',
            'Sale Return',
            'Sale Order',
            'Sale Quotation',
            'POS Sale',
            'POS Return',
            'Delivery',
            'Purchase',
            'Purchase Return',
            'Purchase Order',
            'Purchase Quotation',
            'Goods Receipt',
            'Service Purchase',
            'Service Purchase Return',
            'Service Sale',
            'Service Sale Return',
            'Stock Adjustment',
            'Stock Transfer',
            'Opening Stock',
            'Inventory Count',
            'Production',
            'Production Consumption',
            'Production Finished Goods',
            'Journal Voucher',
            'Payment Voucher',
            'Receipt Voucher',
            'Contra Voucher',
            'Opening Balance',
            'Closing Balance',
            'Year End Closing',
            'Expense',
            'Income',
            'Bank Transfer',
            'Bank Charge',
            'Interest',
            'Customer Payment',
            'Supplier Payment',
            'Customer Receipt',
            'Supplier Receipt',
            'Bank Deposit',
            'Bank Withdrawal',
            'Loan',
            'Loan Repayment',
            'Loan Disbursement',
            'Asset Acquisition',
            'Asset Depreciation',
            'Asset Transfer',
            'Asset Disposal',
            'Payroll',
            'Salary',
            'Advance Salary',
            'Employee Loan',
            'Employee Loan Recovery',
            'Bonus',
            'Overtime',
            'Customer Advance',
            'Supplier Advance',
            'Customer Refund',
            'Supplier Refund',
            'Adjustment',
            'Migration',
            'System'
        ) DEFAULT 'Manual'");
    }

    public function down()
    {
        // Revert to the original enum list (pre-Service Management). Any
        // Service Management journal entries already posted would need to be
        // reversed/deleted before rolling back, or this statement will fail.
        DB::statement("ALTER TABLE journal_entries MODIFY source_type ENUM(
            'Manual',
            'Sale',
            'Sale Return',
            'Sale Order',
            'Sale Quotation',
            'POS Sale',
            'POS Return',
            'Delivery',
            'Purchase',
            'Purchase Return',
            'Purchase Order',
            'Purchase Quotation',
            'Goods Receipt',
            'Stock Adjustment',
            'Stock Transfer',
            'Opening Stock',
            'Inventory Count',
            'Production',
            'Production Consumption',
            'Production Finished Goods',
            'Journal Voucher',
            'Payment Voucher',
            'Receipt Voucher',
            'Contra Voucher',
            'Opening Balance',
            'Closing Balance',
            'Year End Closing',
            'Expense',
            'Income',
            'Bank Transfer',
            'Bank Charge',
            'Interest',
            'Customer Payment',
            'Supplier Payment',
            'Customer Receipt',
            'Supplier Receipt',
            'Bank Deposit',
            'Bank Withdrawal',
            'Loan',
            'Loan Repayment',
            'Loan Disbursement',
            'Asset Acquisition',
            'Asset Depreciation',
            'Asset Transfer',
            'Asset Disposal',
            'Payroll',
            'Salary',
            'Advance Salary',
            'Employee Loan',
            'Employee Loan Recovery',
            'Bonus',
            'Overtime',
            'Customer Advance',
            'Supplier Advance',
            'Customer Refund',
            'Supplier Refund',
            'Adjustment',
            'Migration',
            'System'
        ) DEFAULT 'Manual'");
    }
};
