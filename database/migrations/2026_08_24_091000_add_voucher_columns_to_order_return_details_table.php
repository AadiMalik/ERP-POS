<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mirrors the voucher attribution added to order_details so a partial return
     * can prorate the voucher's own discount and free-quantity share, the same way
     * discount_amount/tax_amount are already prorated per return line.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_return_details', function (Blueprint $table) {
            $table->string('voucher_id')->nullable()->after('discount_amount');
            $table->decimal('voucher_discount_amount', 18, 3)->default(0)->after('voucher_id');
            $table->decimal('free_quantity', 18, 3)->default(0)->after('voucher_discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_return_details', function (Blueprint $table) {
            $table->dropColumn(['voucher_id', 'voucher_discount_amount', 'free_quantity']);
        });
    }
};
