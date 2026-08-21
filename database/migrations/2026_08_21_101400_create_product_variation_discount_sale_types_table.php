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
        Schema::create('product_variation_discount_sale_types', function (Blueprint $table) {
            $table->id();
            $table->string('product_variation_id');
            $table->string('sale_type_id');

            $table->unique(['product_variation_id', 'sale_type_id'], 'pvdst_variation_sale_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variation_discount_sale_types');
    }
};
