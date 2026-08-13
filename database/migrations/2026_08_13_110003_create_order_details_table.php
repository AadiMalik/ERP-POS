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
        Schema::create('order_details', function (Blueprint $table) {
            $table->uuid('order_detail_id')->primary();
            $table->string('order_id');

            $table->string('product_id');
            $table->string('product_variation_id');
            $table->string('product_variation_unit_conversion_id')->nullable();
            $table->string('unit_id')->nullable();
            $table->string('tax_rate_id')->nullable();

            $table->decimal('quantity', 18, 3)->default(0);
            $table->decimal('conversion_factor', 18, 6)->default(1);
            $table->decimal('base_quantity', 18, 3)->default(0);

            $table->decimal('unit_price', 18, 3)->default(0);
            $table->decimal('discount', 18, 3)->default(0);
            $table->decimal('discount_amount', 18, 3)->default(0);
            $table->decimal('tax', 18, 3)->default(0);
            $table->decimal('tax_amount', 18, 3)->default(0);
            $table->decimal('subtotal', 18, 3)->default(0);
            $table->decimal('total', 18, 3)->default(0);

            // Snapshot of ProductVariationStock.avg_price at the moment the order
            // is POSTED (not when the line is drafted) - the COGS leg of the sale
            // JV and any later reporting are valued from this, never recomputed.
            $table->decimal('cost_price', 18, 3)->default(0);

            $table->text('notes')->nullable();

            $table->integer('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_details');
    }
};
