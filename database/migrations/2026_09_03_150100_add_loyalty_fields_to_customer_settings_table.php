<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the existing (previously unused) Loyalty Program fields on
 * customer_settings: how points are earned (whole order vs individual
 * loyalty-enabled products/variations) and the currency value of one point
 * when redeemed at checkout.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('customer_settings', function (Blueprint $table) {
            $table->enum('loyalty_earning_mode', ['order', 'product'])->default('order')->after('loyalty_program');
            $table->decimal('loyalty_redemption_value', 18, 3)->default(1.00)->after('loyalty_min_order_amount');
        });
    }

    public function down()
    {
        Schema::table('customer_settings', function (Blueprint $table) {
            $table->dropColumn(['loyalty_earning_mode', 'loyalty_redemption_value']);
        });
    }
};
