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
            $table->boolean('manual_payment_account_selection')->default(false);
            $table->uuid('default_withholding_tax_account_id')->nullable();
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
            $table->dropColumn('manual_payment_account_selection');
            $table->dropColumn('default_withholding_tax_account_id');
        });
    }
};
