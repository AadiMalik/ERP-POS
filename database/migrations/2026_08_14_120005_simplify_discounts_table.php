<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A Discount is now just a named pricing rule (name/type/value/status) -
     * every condition that gates WHEN it can be used (code, validity window,
     * usage limits, min order amount, product/category/customer/branch
     * targeting) is a Voucher concern, not a Discount one.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn([
                'code',
                'valid_from',
                'valid_to',
                'usage_limit_total',
                'usage_limit_per_customer',
                'used_count',
                'min_order_amount',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
            $table->date('valid_from')->nullable()->after('value');
            $table->date('valid_to')->nullable()->after('valid_from');
            $table->integer('usage_limit_total')->nullable()->after('valid_to');
            $table->integer('usage_limit_per_customer')->nullable()->after('usage_limit_total');
            $table->integer('used_count')->default(0)->after('usage_limit_per_customer');
            $table->decimal('min_order_amount', 18, 3)->nullable()->after('used_count');
        });
    }
};
