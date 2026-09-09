<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshots the tax mode in effect at sale time onto the order itself -
 * receipts/reports/JV must always read this stamped value, never re-resolve
 * the branch's current setting live, so a reprinted/re-audited historical
 * order stays correct even after the branch's tax type is later changed.
 * Defaulting existing rows to 'exclusive' is correct since that was the only
 * mode this system has ever computed.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('tax_type', ['inclusive', 'exclusive'])->default('exclusive')->after('tax_amount');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('tax_type');
        });
    }
};
