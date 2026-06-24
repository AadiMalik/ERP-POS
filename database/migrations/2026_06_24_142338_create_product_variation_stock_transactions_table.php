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
        Schema::create('product_variation_stock_transactions', function (Blueprint $table) {
            $table->uuid('product_variation_stock_transaction_id')->primary();
            $table->timestamp('transaction_date')->default(now());
            $table->uuid('business_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('product_variation_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            $table->uuid('unit_id')->nullable();
            $table->uuid('product_variation_unit_conversion_id')->nullable();
            $table->decimal('conversion_factor', 18, 4)->default(1.0000);
            $table->decimal('quantity', 18, 4)->default(0.0000);
            $table->decimal('base_quantity', 18, 4)->default(0.0000);
            $table->decimal('unit_price', 18, 4)->default(0.0000);
            $table->decimal('total_price', 18, 4)->default(0.0000);
            $table->decimal('quantity_after', 18, 4)->default(0.0000);
            $table->decimal('avg_price_after', 18, 4)->default(0.0000);
            $table->uuid('reference_id')->nullable();
            $table->enum('transaction_type', [
                'opening',
                'purchase',
                'purchase_return',
                'sale',
                'sale_return',
                'stock_take_increase',
                'stock_take_decrease',
                'adjustment',
                'damage',
                'expired',
                'wastage',
                'consumption',
                'gift',
                'sample',
                'transfer_in',
                'transfer_out',
                'production_in',
                'production_out'
            ])->nullable();
            $table->enum('reference_type', [
                'opening_stock',
                'purchase',
                'grn',
                'purchase_return',
                'sale',
                'sale_return',
                'stock_taking',
                'damage_note',
                'expiry_note',
                'wastage_note',
                'stock_transfer',
                'production',
                'consumption',
                'gift',
                'sample',
                'manual'
            ])->nullable();
            $table->text('remarks')->nullable();
            $table->uuid('product_variation_batch_id')->nullable();

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
        Schema::dropIfExists('product_variation_stock_transactions');
    }
};
