<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('website_cart_items', function (Blueprint $table) {
            $table->uuid('cart_item_id')->primary();
            $table->uuid('cart_id');
            $table->string('product_id');
            $table->string('product_variation_id');
            $table->decimal('quantity', 18, 3)->default(1);
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();

            $table->unique(['cart_id', 'product_variation_id'], 'website_cart_items_cart_variation_unique');
            $table->index('cart_id');
            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('website_cart_items');
    }
};
