<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a business opt a specific product into the Loyalty Program when
 * CustomerSetting.loyalty_earning_mode = 'product'. Ignored entirely when the
 * business earns loyalty points on the overall order instead.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_loyalty_enabled')->default(false)->after('is_best_seller');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_loyalty_enabled');
        });
    }
};
