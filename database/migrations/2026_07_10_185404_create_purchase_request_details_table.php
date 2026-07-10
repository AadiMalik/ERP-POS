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
        Schema::create('purchase_request_details', function (Blueprint $table) {
            $table->uuid('purchase_request_detail_id')->primary();
            $table->uuid('purchase_request_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('product_variation_id')->nullable();
            $table->decimal('requested_quantity', 18, 3)->nullable();
            $table->uuid('unit_id')->nullable();
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
        Schema::dropIfExists('purchase_request_details');
    }
};
