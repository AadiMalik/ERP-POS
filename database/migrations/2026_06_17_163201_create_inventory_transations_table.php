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
        Schema::create('inventory_transations', function (Blueprint $table) {
            $table->uuid('inventory_transaction_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('product_variation_id')->nullable();
            $table->enum('type', [
                'opening_stock',
                'purchase',
                'sale',
                'sale_return',
                'purchase_return',
                'adjustment',
                'damage',
                'consumption',
                'transfer_in',
                'transfer_out',
                'stock_take'
            ])->nullable();
            $table->decimal('quantity', 18, 4)->default(0.0000);
            $table->decimal('unit_cost', 18, 4)->default(0.0000);
            $table->text('notes')->nullable();
            $table->timestamp('transaction_date')->default(now());

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
        Schema::dropIfExists('inventory_transations');
    }
};
