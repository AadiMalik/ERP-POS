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
        Schema::table('pos_settings', function (Blueprint $table) {
            // Master switch for selling below a variation's Minimum Selling
            // Price. Only takes effect when allow_price_change_in_cart is
            // also on - price editing itself must be enabled first - and the
            // acting user still needs the order.price.override-minimum
            // permission. Replaces the old per-sale #overrideMinPriceCheck
            // POS-screen checkbox, which is now business-wide instead of a
            // manual per-checkout toggle.
            $table->boolean('allow_price_below_minimum')->default(false)->after('allow_price_change_in_cart');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->dropColumn('allow_price_below_minimum');
        });
    }
};
