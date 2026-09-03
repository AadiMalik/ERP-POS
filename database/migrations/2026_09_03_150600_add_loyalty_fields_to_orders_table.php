<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Order-level loyalty figures, frozen at save()/post() time - mirrors
 * voucher_discount_amount. Storing the actual computed values (not just a
 * flag) means a later change to CustomerSetting's loyalty configuration never
 * alters the loyalty history of an already-placed order.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('loyalty_points_used', 18, 3)->default(0)->after('voucher_discount_amount');
            $table->decimal('loyalty_discount_amount', 18, 3)->default(0)->after('loyalty_points_used');
            $table->decimal('loyalty_points_earned', 18, 3)->default(0)->after('loyalty_discount_amount');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['loyalty_points_used', 'loyalty_discount_amount', 'loyalty_points_earned']);
        });
    }
};
