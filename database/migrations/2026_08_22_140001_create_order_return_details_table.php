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
        Schema::create('order_return_details', function (Blueprint $table) {
            $table->uuid('order_return_detail_id')->primary();
            $table->uuid('order_return_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->uuid('order_detail_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('product_variation_id')->nullable();
            $table->uuid('product_variation_unit_conversion_id')->nullable();
            $table->uuid('unit_id')->nullable();

            $table->decimal('ordered_quantity', 18, 3)->nullable();
            $table->decimal('already_returned_quantity', 18, 3)->default(0.000);
            $table->decimal('return_quantity', 18, 3)->nullable();
            $table->decimal('conversion_factor', 18, 3)->nullable();
            $table->decimal('base_quantity', 18, 3)->nullable();

            $table->decimal('unit_price', 18, 3)->nullable();
            $table->decimal('discount', 18, 3)->default(0.000);
            $table->decimal('discount_amount', 18, 3)->default(0.000);
            $table->decimal('tax', 18, 3)->default(0.000);
            $table->decimal('tax_amount', 18, 3)->default(0.000);
            $table->decimal('subtotal', 18, 3)->default(0.000);
            $table->decimal('total', 18, 3)->default(0.000);
            $table->decimal('cost_price', 18, 3)->default(0.000);

            $table->text('reason')->nullable();
            $table->text('description')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();

            $table->index('order_return_id');
            $table->index('order_detail_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_return_details');
    }
};
