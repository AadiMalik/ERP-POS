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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('purchase_order_id')->primary();
            $table->uuid('supplier_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->uuid('business_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            $table->string('purchase_order_no')->nullable();
            $table->timestamp('purchase_order_date')->nullable();
            $table->timestamp('purchase_expected_date')->nullable();
            $table->text('description')->nullable();
            $table->decimal('subtotal', 18, 3)->nullable();
            $table->decimal('discount', 18, 3)->nullable();
            $table->decimal('discount_amount', 18, 3)->nullable();
            $table->decimal('tax', 18, 3)->nullable();
            $table->decimal('tax_amount', 18, 3)->nullable();
            $table->decimal('shipping_charge', 18, 3)->nullable();
            $table->decimal('total', 18, 3)->nullable();
            $table->enum('status', ['pending', 'approved', 'completed', 'cancelled'])->default('pending');
            
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
};
