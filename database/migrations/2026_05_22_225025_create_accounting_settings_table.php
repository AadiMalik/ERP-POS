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
        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->nullable();
            $table->boolean('enable_accounting')->default(true);
            $table->integer('default_cash_account_id')->nullable();
            $table->integer('default_bank_account_id')->nullable();
            $table->integer('default_discount_account_id')->nullable();
            $table->integer('default_tax_account_id')->nullable();
            $table->integer('default_revenue_account_id')->nullable();
            $table->integer('default_purchase_account_id')->nullable();
            $table->integer('default_expense_account_id')->nullable();
            $table->integer('default_supplier_account_id')->nullable();
            $table->integer('default_customer_account_id')->nullable();
            $table->string('currency')->nullable();
            $table->string('currency_symbol')->nullable();
            $table->enum('currency_position', ['before', 'after'])->default('before');
            $table->integer('decimal_points')->default(2);

            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounting_settings');
    }
};
