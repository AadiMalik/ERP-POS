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
        Schema::create('product_variation_price_histories', function (Blueprint $table) {
            $table->uuid('product_variation_price_history_id')->primary();
            $table->string('business_id');
            $table->string('product_variation_id');
            $table->string('sale_type_id');

            $table->decimal('old_price', 18, 4)->nullable();
            $table->decimal('new_price', 18, 4);

            $table->integer('changedby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->index(['product_variation_id', 'sale_type_id'], 'pvph_variation_sale_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variation_price_histories');
    }
};
