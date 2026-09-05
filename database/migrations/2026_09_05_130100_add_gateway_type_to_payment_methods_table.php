<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a 'gateway' payment_methods.type so an active online Payment Gateway
 * (see payment_gateways table) plugs into the existing order_payments/
 * accounting tender pipeline via one linked payment_methods row, instead of
 * inventing a second payment-method vocabulary. Gateway rows are always
 * is_website_only (see 2026_08_26_150000) so they can never appear in POS.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('payment_methods', 'payment_gateway_id')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('payment_gateway_id')->nullable()->after('account_id');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payment_methods MODIFY type ENUM('cash', 'card', 'bank', 'credit', 'store_credit', 'wallet', 'other', 'cod', 'gateway')");
        }
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('payment_methods')->where('type', 'gateway')->update(['type' => 'other']);
            DB::statement("ALTER TABLE payment_methods MODIFY type ENUM('cash', 'card', 'bank', 'credit', 'store_credit', 'wallet', 'other', 'cod')");
        }

        if (Schema::hasColumn('payment_methods', 'payment_gateway_id')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropColumn('payment_gateway_id');
            });
        }
    }
};
