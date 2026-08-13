<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Isolates the voucher's own discount contribution from the combined
     * orders.discount_amount (line + order-discount + voucher), so voucher
     * redemption can be recorded against its actual amount instead of the
     * whole order's discount.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('voucher_discount_amount', 18, 3)->default(0)->after('voucher_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('voucher_discount_amount');
        });
    }
};
