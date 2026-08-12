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
        Schema::create('transfer_note_details', function (Blueprint $table) {
            $table->uuid('transfer_note_detail_id')->primary();
            $table->uuid('transfer_note_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('product_variation_id')->nullable();
            $table->uuid('product_variation_unit_conversion_id')->nullable();
            $table->uuid('unit_id')->nullable();

            $table->decimal('conversion_factor', 18, 3)->default(1.000);
            $table->decimal('available_quantity', 18, 3)->default(0.000);
            $table->decimal('transfer_quantity', 18, 3)->nullable();
            $table->decimal('base_quantity', 18, 3)->nullable();
            $table->decimal('unit_cost', 18, 3)->default(0.000);
            $table->decimal('total_value', 18, 3)->default(0.000);

            $table->text('description')->nullable();

            $table->uuid('createdby_id')->nullable();
            $table->uuid('updatedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfer_note_details');
    }
};
