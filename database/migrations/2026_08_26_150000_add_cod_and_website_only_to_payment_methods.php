<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds website-only COD payment support without exposing it on POS.
 * - type `cod` joins the payment_methods enum
 * - is_website_only flags methods that must never appear in POS lists
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('payment_methods', 'is_website_only')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->boolean('is_website_only')->default(false)->after('is_default');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payment_methods MODIFY type ENUM('cash', 'card', 'bank', 'credit', 'store_credit', 'wallet', 'other', 'cod')");
        }
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('payment_methods')->where('type', 'cod')->update(['type' => 'other']);
            DB::statement("ALTER TABLE payment_methods MODIFY type ENUM('cash', 'card', 'bank', 'credit', 'store_credit', 'wallet', 'other')");
        }

        if (Schema::hasColumn('payment_methods', 'is_website_only')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropColumn('is_website_only');
            });
        }
    }
};
