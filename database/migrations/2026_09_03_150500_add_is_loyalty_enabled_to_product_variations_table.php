<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Variation-level counterpart to products.is_loyalty_enabled - lets a
 * business opt a specific variation into the Loyalty Program (rather than
 * every variation of its parent product) when
 * CustomerSetting.loyalty_earning_mode = 'product'.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->boolean('is_loyalty_enabled')->default(false)->after('discount_apply_all');
        });
    }

    public function down()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn('is_loyalty_enabled');
        });
    }
};
