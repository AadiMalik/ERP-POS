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
        Schema::create('product_variation_prices', function (Blueprint $table) {
            $table->uuid('product_variation_price_id')->primary();
            $table->string('business_id');
            $table->string('product_variation_id');
            $table->string('sale_type_id');

            $table->decimal('price', 18, 4)->default(0);

            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();

            // One row per (variation, sale type) - rows are deleted and
            // reinserted wholesale on every variation save, same pattern as
            // product_variation_attributes.
            $table->unique(['product_variation_id', 'sale_type_id'], 'pvp_variation_sale_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variation_prices');
    }
};
