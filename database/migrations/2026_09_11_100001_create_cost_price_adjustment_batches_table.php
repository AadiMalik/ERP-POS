<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Only populated when the adjusted product variation is batch/expiry-tracked
 * (ProductVariationBatch rows exist for the target warehouse) - one row per
 * batch revalued by a CostPriceAdjustment, capturing the batch's avg_price
 * before/after so an approved adjustment can be reversed back to the exact
 * per-batch cost it had, not just the warehouse-level average.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cost_price_adjustment_batches', function (Blueprint $table) {
            $table->uuid('cost_price_adjustment_batch_id')->primary();
            $table->uuid('cost_price_adjustment_id')->nullable();
            $table->uuid('product_variation_batch_id')->nullable();
            $table->string('batch_no')->nullable();

            $table->decimal('quantity', 18, 4)->default(0.0000);
            $table->decimal('previous_batch_avg_price', 18, 4)->default(0.0000);
            $table->decimal('new_batch_avg_price', 18, 4)->default(0.0000);
            $table->decimal('batch_adjustment_amount', 18, 4)->default(0.0000);

            $table->timestamp('date_created')->nullable();

            $table->index('cost_price_adjustment_id');
            $table->index('product_variation_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cost_price_adjustment_batches');
    }
};
