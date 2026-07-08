<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->uuid('default_carriage_account_id')->nullable();
            $table->uuid('default_round_off_account_id')->nullable();
            $table->uuid('default_purchase_return_account_id')->nullable();
            $table->uuid('default_sale_account_id')->nullable();
            $table->uuid('default_sale_return_account_id')->nullable();
            $table->uuid('default_inventory_account_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn('default_carriage_account_id');
            $table->dropColumn('default_round_off_account_id');
            $table->dropColumn('default_purchase_return_account_id');
            $table->dropColumn('default_sale_return_account_id');
            $table->dropColumn('default_inventory_account_id');
        });
    }
};
