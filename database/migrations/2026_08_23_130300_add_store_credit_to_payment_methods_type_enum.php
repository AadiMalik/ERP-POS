<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds 'store_credit' as a new payment_methods.type value, distinct from
 * the existing (unused) 'wallet' value to keep the concept unambiguous -
 * see PaymentMethodService::save() for how it (like 'credit') is exempted
 * from requiring its own mapped account_id. Phase 2 plan, batch E.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payment_methods MODIFY type ENUM('cash', 'card', 'bank', 'credit', 'store_credit', 'wallet', 'other')");
        }
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('payment_methods')->where('type', 'store_credit')->update(['type' => 'other']);
            DB::statement("ALTER TABLE payment_methods MODIFY type ENUM('cash', 'card', 'bank', 'credit', 'wallet', 'other')");
        }
    }
};
