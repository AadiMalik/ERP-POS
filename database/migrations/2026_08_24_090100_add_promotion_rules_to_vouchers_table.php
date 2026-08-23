<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds scheduling, capping, exclusivity, and BOGO/buy-X-get-Y rule fields to
     * Voucher - Discount stays the simple flat rate it already is (see
     * 2026_08_14_120005_simplify_discounts_table.php), all new conditional
     * complexity lives on Voucher only.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->enum('promo_type', ['discount', 'bogo', 'buy_x_get_y'])->default('discount')->after('type');

            // CSV of 0 (Sunday) - 6 (Saturday); null/empty = every day.
            $table->string('days_of_week')->nullable()->after('valid_to');
            $table->time('time_start')->nullable()->after('days_of_week');
            $table->time('time_end')->nullable()->after('time_start');

            // Caps the payout of a percent-type voucher; irrelevant for fixed-type.
            $table->decimal('max_discount_amount', 18, 3)->nullable()->after('min_order_amount');

            // When true, this voucher cannot be combined with an order-level Discount.
            $table->boolean('is_exclusive')->default(false)->after('max_discount_amount');

            // BOGO / buy-X-get-Y config - null unless promo_type is one of those.
            $table->integer('buy_quantity')->nullable()->after('is_exclusive');
            $table->integer('get_quantity')->nullable()->after('buy_quantity');
            $table->decimal('get_discount_percent', 5, 2)->default(100)->after('get_quantity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn([
                'promo_type',
                'days_of_week',
                'time_start',
                'time_end',
                'max_discount_amount',
                'is_exclusive',
                'buy_quantity',
                'get_quantity',
                'get_discount_percent',
            ]);
        });
    }
};
