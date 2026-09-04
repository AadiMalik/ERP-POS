<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every other domain table carries branch_id for applyRoleScope()'s
 * branch-level role scoping (BranchAdmin/POSManager/etc.) - this table
 * was missing it. See resources/docs/developer/18-serial-number-tracking.md.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('product_variation_serial_movements', function (Blueprint $table) {
            $table->uuid('branch_id')->nullable()->after('business_id');
        });
    }

    public function down()
    {
        Schema::table('product_variation_serial_movements', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
    }
};
