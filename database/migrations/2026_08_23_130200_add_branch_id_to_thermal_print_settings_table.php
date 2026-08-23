<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branch-level thermal receipt config, falling back to the business default
 * when a branch has no row of its own - mirrors how
 * add_document_type_to_print_settings_table.php extended print_settings
 * with a second scoping dimension. A row with branch_id = null is (as
 * today) the business default; a row with branch_id set is that branch's
 * override, auto-created only when an admin explicitly saves one (never
 * lazily auto-created just because a print was resolved for that branch -
 * see ThermalPrintSettingResolverService). Phase 2 plan, batch D.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('thermal_print_settings', function (Blueprint $table) {
            $table->string('branch_id')->nullable()->after('business_id');
            $table->index(['business_id', 'branch_id']);
        });
    }

    public function down()
    {
        Schema::table('thermal_print_settings', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
