<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A sale line only gets rows here when it had to be fulfilled from more
     * than one batch (FEFO/FIFO draw-down spanning batches); the common
     * single-batch case is recorded directly on order_details.product_variation_batch_id
     * instead, so most queries never need to join this table.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_detail_batches', function (Blueprint $table) {
            $table->uuid('order_detail_batch_id')->primary();
            $table->uuid('order_detail_id');
            $table->uuid('product_variation_batch_id');
            $table->decimal('quantity', 18, 3)->default(0.000);
            $table->decimal('base_quantity', 18, 3)->default(0.000);

            $table->uuid('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->index('order_detail_id');
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
        Schema::dropIfExists('order_detail_batches');
    }
};
