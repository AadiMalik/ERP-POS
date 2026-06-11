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
        Schema::create('inventory_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->nullable();
            $table->boolean('stock_tracking')->default(true);
            $table->boolean('negative_stock')->default(false);
            $table->boolean('low_stock_alert')->default(true);
            $table->integer('low_stock_quantity')->default(5);
            $table->enum('barcode_type',['CODE128', 'EAN13', 'UPCA'])->default('CODE128');
            $table->boolean('auto_generate_sku')->default(true);
            $table->boolean('enable_batch_no')->default(false);
            $table->boolean('enable_expiry_date')->default(false);

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
        Schema::dropIfExists('inventory_settings');
    }
};
