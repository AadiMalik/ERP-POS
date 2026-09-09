<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inclusive sales whose cash and card rates differ stamp the leftover rate
 * (max(cash, card) - applied) as tax_discount / tax_discount_amount. The
 * customer-facing total does not change; the leftover is posted to its own
 * COA account (default_tax_discount_account_id) instead of Tax Payable.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('tax_discount', 18, 3)->default(0)->after('tax_type');
            $table->decimal('tax_discount_amount', 18, 3)->default(0)->after('tax_discount');
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->decimal('tax_discount', 18, 3)->default(0)->after('tax');
            $table->decimal('tax_discount_amount', 18, 3)->default(0)->after('tax_amount');
        });

        Schema::table('order_returns', function (Blueprint $table) {
            $table->decimal('tax_discount', 18, 3)->default(0)->after('tax_amount');
            $table->decimal('tax_discount_amount', 18, 3)->default(0)->after('tax_discount');
        });

        Schema::table('order_return_details', function (Blueprint $table) {
            $table->decimal('tax_discount', 18, 3)->default(0)->after('tax');
            $table->decimal('tax_discount_amount', 18, 3)->default(0)->after('tax_amount');
        });

        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->uuid('default_tax_discount_account_id')->nullable()->after('default_tax_account_id');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tax_discount', 'tax_discount_amount']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['tax_discount', 'tax_discount_amount']);
        });

        Schema::table('order_returns', function (Blueprint $table) {
            $table->dropColumn(['tax_discount', 'tax_discount_amount']);
        });

        Schema::table('order_return_details', function (Blueprint $table) {
            $table->dropColumn(['tax_discount', 'tax_discount_amount']);
        });

        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn('default_tax_discount_account_id');
        });
    }
};
