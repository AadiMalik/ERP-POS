<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->uuid('default_service_purchase_account_id')->nullable()->after('default_purchase_return_account_id');
            $table->uuid('default_service_purchase_return_account_id')->nullable()->after('default_service_purchase_account_id');
            $table->uuid('default_service_sale_account_id')->nullable()->after('default_service_purchase_return_account_id');
            $table->uuid('default_service_sale_return_account_id')->nullable()->after('default_service_sale_account_id');
        });
    }

    public function down()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn([
                'default_service_purchase_account_id',
                'default_service_purchase_return_account_id',
                'default_service_sale_account_id',
                'default_service_sale_return_account_id',
            ]);
        });
    }
};
