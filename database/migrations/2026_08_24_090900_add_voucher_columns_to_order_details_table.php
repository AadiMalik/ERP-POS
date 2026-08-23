<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per-line voucher attribution - `discount_amount` keeps its existing meaning
     * (line-level % discount only); this is the voucher's own contribution to this
     * specific line, plus how many units of it were free (BOGO/buy-X-get-Y), so the
     * order-detail view and returns can show/prorate exactly what a voucher touched.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_details', function (Blueprint $table) {
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
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['voucher_id', 'voucher_discount_amount', 'free_quantity']);
        });
    }
};
