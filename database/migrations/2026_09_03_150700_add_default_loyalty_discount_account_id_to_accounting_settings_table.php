<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GL account the loyalty discount (contra-revenue) leg of the Sale Voucher
 * journal entry is posted to when a customer redeems loyalty points -
 * business-specific, auto-provisioned via ChartOfAccountsCloneService /
 * AccountingSettingCloneService, never hard-coded by id.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->uuid('default_loyalty_discount_account_id')->nullable()->after('default_discount_account_id');
        });
    }

    public function down()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn('default_loyalty_discount_account_id');
        });
    }
};
