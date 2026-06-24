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
        Schema::create('product_variation_batches', function (Blueprint $table) {
            $table->uuid('product_variation_batch_id')->primary();
            $table->string('batch_no')->nullable();
            $table->uuid('business_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->uuid('product_variation_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            $table->decimal('avg_price', 18, 4)->default(0.0000);
            $table->decimal('quantity', 18, 4)->default(0.0000);
            $table->date('manufacturing_date')->default(now());
            $table->date('expiry_date')->default(now());

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
        Schema::dropIfExists('product_variation_batches');
    }
};
