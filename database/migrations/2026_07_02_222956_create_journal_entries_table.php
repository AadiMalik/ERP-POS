<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('journal_entry_id')->primary();
            $table->uuid('journal_id')->nullable();
            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->string('entry_no')->nullable();
            $table->string('reference_no')->nullable();
            $table->timestamp('entry_date')->nullable();
            $table->string('description')->nullable();
            $table->enum('source_type', [
                'Manual',

                // Sales
                'Sale',
                'Sale Return',
                'Sale Order',
                'Sale Quotation',
                'POS Sale',
                'POS Return',
                'Delivery',

                // Purchase
                'Purchase',
                'Purchase Return',
                'Purchase Order',
                'Purchase Quotation',
                'Goods Receipt',

                // Inventory
                'Stock Adjustment',
                'Stock Transfer',
                'Opening Stock',
                'Inventory Count',
                'Production',
                'Production Consumption',
                'Production Finished Goods',

                // Accounting
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

                // Finance
                'Loan',
                'Loan Repayment',
                'Loan Disbursement',

                // Assets
                'Asset Acquisition',
                'Asset Depreciation',
                'Asset Transfer',
                'Asset Disposal',

                // HR
                'Payroll',
                'Salary',
                'Advance Salary',
                'Employee Loan',
                'Employee Loan Recovery',
                'Bonus',
                'Overtime',

                // CRM
                'Customer Advance',
                'Supplier Advance',
                'Customer Refund',
                'Supplier Refund',
                // Misc
                'Adjustment',
                'Migration',
                'System',
            ])->default('Manual');
            $table->uuid('source_id')->nullable();
            $table->enum('status', ['pending', 'posted'])->default('pending');
            $table->integer('postedby_id')->nullable();
            $table->timestamp('date_posted')->nullable();


            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('journal_entries');
    }
};
