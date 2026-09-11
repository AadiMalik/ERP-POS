<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->uuid('default_complimentary_expense_account_id')->nullable()->after('default_cogs_account_id');
        });
    }

    public function down()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn('default_complimentary_expense_account_id');
        });
    }
};
