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
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->uuid('warehouse_stock_id')->primary();
            $table->uuid('warehouse_id')->nullable();
            $table->uuid('business_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('product_variation_id')->nullable();
            $table->decimal('quantity', 18, 4)->default(0.0000);
            $table->decimal('reserved_quantity', 18, 4)->default(0.0000);
            $table->decimal('available_quantity', 18, 4)->default(0.0000);
            $table->decimal('average_cost', 18, 4)->default(0.0000);
            $table->timestamp('last_stock_take_at')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
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
        Schema::dropIfExists('warehouse_stocks');
    }
};
