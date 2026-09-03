<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * customer_profiles.loyalty_points (already existed, unused) becomes the
 * "available" balance; this adds the sibling "reserved" balance so staff/
 * customers can see points locked against an unpaid order separately from
 * points that are actually spendable - mirrors store_credit_balance's
 * aggregate+ledger pattern (see customer_loyalty_transactions).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->decimal('loyalty_points_reserved', 18, 3)->default(0)->after('loyalty_points');
        });
    }

    public function down()
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn('loyalty_points_reserved');
        });
    }
};
