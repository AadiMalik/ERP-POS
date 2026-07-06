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
        Schema::create('good_receipt_note_details', function (Blueprint $table) {
            $table->uuid('good_receipt_note_detail_id')->primary();
            $table->uuid('good_receipt_note_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('product_variation_id')->nullable();
            $table->decimal('received_quantity', 18, 3)->nullable();
            $table->decimal('base_quantity', 18, 3)->nullable();
            $table->uuid('unit_id')->nullable();
            $table->decimal('conversion_factor', 18, 3)->nullable();
            $table->decimal('unit_price', 18, 3)->nullable();
            $table->decimal('total', 18, 3)->nullable();
            $table->uuid('product_variation_batch_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('good_receipt_note_details');
    }
};
