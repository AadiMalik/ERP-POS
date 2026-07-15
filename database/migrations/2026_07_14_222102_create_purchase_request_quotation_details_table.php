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
        Schema::create('purchase_request_quotation_details', function (Blueprint $table) {
            $table->uuid('purchase_request_quotation_detail_id')->primary();
            $table->uuid('purchase_request_quotation_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('product_variation_id')->nullable();

            $table->decimal('requested_quantity', 18, 3)->default(0.000);
            $table->decimal('quoted_quantity', 18, 3)->default(0.000);
            $table->uuid('unit_id')->nullable();
            $table->decimal('unit_price', 18, 3)->default(0.000);
            $table->decimal('discount', 18, 3)->default(0.000);
            $table->decimal('discount_amount', 18, 3)->default(0.000);
            $table->decimal('tax', 18, 3)->default(0.000);
            $table->decimal('tax_amount', 18, 3)->default(0.000);
            $table->decimal('subtotal', 18, 3)->default(0.000);
            $table->decimal('total', 18, 3)->default(0.000);
            $table->enum('status', [
                'available',
                'out of stock'
            ])->default('available');
            $table->text('description')->nullable();
            
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();

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
        Schema::dropIfExists('purchase_request_quotation_details');
    }
};
