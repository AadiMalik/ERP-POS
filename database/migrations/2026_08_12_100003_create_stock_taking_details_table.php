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
        Schema::create('stock_taking_details', function (Blueprint $table) {
            $table->uuid('stock_taking_detail_id')->primary();
            $table->uuid('stock_taking_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('product_variation_id')->nullable();
            $table->uuid('unit_id')->nullable();

            $table->decimal('system_quantity', 18, 3)->default(0.000);
            $table->decimal('physical_quantity', 18, 3)->default(0.000);
            $table->decimal('difference_quantity', 18, 3)->default(0.000);
            $table->decimal('unit_cost', 18, 3)->default(0.000);
            $table->decimal('difference_value', 18, 3)->default(0.000);

            $table->text('reason')->nullable();
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
        Schema::dropIfExists('stock_taking_details');
    }
};
