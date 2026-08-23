<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stored aggregate balance, alongside the existing loyalty_points precedent
 * on this table - the authoritative "how much can this customer redeem at
 * POS" figure, maintained transactionally by
 * customer_store_credit_transactions (the ledger - see that table's
 * migration). Phase 2 plan, batch E.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->decimal('store_credit_balance', 18, 3)->default(0)->after('loyalty_points');
        });
    }

    public function down()
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn('store_credit_balance');
        });
    }
};
