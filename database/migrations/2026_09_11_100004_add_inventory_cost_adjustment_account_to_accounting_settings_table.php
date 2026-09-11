<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GL account the Cost Price Adjustment JV's revaluation leg is posted to -
 * dedicated and separate from default_stock_adjustment_account_id (which is
 * for physical-loss write-offs) so a pure valuation correction never mixes
 * with quantity shrinkage in the P&L. Business-specific, auto-provisioned via
 * ChartOfAccountsCloneService / AccountingSettingCloneService, never
 * hard-coded by id. Mirrors 2026_09_03_150700_add_default_loyalty_discount_account_id_to_accounting_settings_table.php.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->uuid('default_inventory_cost_adjustment_account_id')->nullable()->after('default_stock_adjustment_account_id');
        });
    }

    public function down()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn('default_inventory_cost_adjustment_account_id');
        });
    }
};
