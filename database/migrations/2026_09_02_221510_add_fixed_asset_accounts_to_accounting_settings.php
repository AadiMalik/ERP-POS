<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->uuid('default_fixed_asset_account_id')->nullable()->after('default_withholding_tax_account_id');
            $table->uuid('default_accumulated_depreciation_account_id')->nullable()->after('default_fixed_asset_account_id');
            $table->uuid('default_depreciation_expense_account_id')->nullable()->after('default_accumulated_depreciation_account_id');
            $table->uuid('default_gain_on_asset_disposal_account_id')->nullable()->after('default_depreciation_expense_account_id');
            $table->uuid('default_loss_on_asset_disposal_account_id')->nullable()->after('default_gain_on_asset_disposal_account_id');
        });
    }

    public function down()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn([
                'default_fixed_asset_account_id',
                'default_accumulated_depreciation_account_id',
                'default_depreciation_expense_account_id',
                'default_gain_on_asset_disposal_account_id',
                'default_loss_on_asset_disposal_account_id',
            ]);
        });
    }
};
