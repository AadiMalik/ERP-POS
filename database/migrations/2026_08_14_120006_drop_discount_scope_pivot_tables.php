<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Discount is no longer a scoped/conditional campaign (that's what
     * Voucher's own product/category/customer/order_type/branch pivots are
     * for) - it's a flat named rate applicable to any order.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('discount_products');
        Schema::dropIfExists('discount_categories');
        Schema::dropIfExists('discount_customers');
        Schema::dropIfExists('discount_order_types');
        Schema::dropIfExists('discount_branches');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('discount_products', function (Blueprint $table) {
            $table->id();
            $table->string('discount_id');
            $table->string('product_id');
        });

        Schema::create('discount_categories', function (Blueprint $table) {
            $table->id();
            $table->string('discount_id');
            $table->string('category_id');
        });

        Schema::create('discount_customers', function (Blueprint $table) {
            $table->id();
            $table->string('discount_id');
            $table->string('customer_id');
        });

        Schema::create('discount_order_types', function (Blueprint $table) {
            $table->id();
            $table->string('discount_id');
            $table->string('order_type_id');
        });

        Schema::create('discount_branches', function (Blueprint $table) {
            $table->id();
            $table->string('discount_id');
            $table->string('branch_id');
        });
    }
};
