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
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->uuid('default_cash_account_id')->nullable()->change();
            $table->uuid('default_bank_account_id')->nullable()->change();
            $table->uuid('default_discount_account_id')->nullable()->change();
            $table->uuid('default_tax_account_id')->nullable()->change();
            $table->uuid('default_revenue_account_id')->nullable()->change();
            $table->uuid('default_purchase_account_id')->nullable()->change();
            $table->uuid('default_expense_account_id')->nullable()->change();
            $table->uuid('default_supplier_account_id')->nullable()->change();
            $table->uuid('default_customer_account_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->integer('default_cash_account_id')->nullable()->change();
            $table->integer('default_bank_account_id')->nullable()->change();
            $table->integer('default_discount_account_id')->nullable()->change();
            $table->integer('default_tax_account_id')->nullable()->change();
            $table->integer('default_revenue_account_id')->nullable()->change();
            $table->integer('default_purchase_account_id')->nullable()->change();
            $table->integer('default_expense_account_id')->nullable()->change();
            $table->integer('default_supplier_account_id')->nullable()->change();
            $table->integer('default_customer_account_id')->nullable()->change();
        });
    }
};
