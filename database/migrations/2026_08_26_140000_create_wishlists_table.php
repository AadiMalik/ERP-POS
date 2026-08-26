<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Storefront wishlist entries. Supports product-level rows
     * (product_variation_id NULL) and variation-level rows. The item_key
     * column makes uniqueness reliable under MySQL's NULL-unique behaviour.
     */
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->uuid('wishlist_id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('business_id');
            $table->string('product_id');
            $table->string('product_variation_id')->nullable();
            // "{product_id}" for product-level, "{product_id}:{variation_id}" for variation-level
            $table->string('item_key');
            $table->timestamp('date_created')->nullable();

            $table->unique(['user_id', 'business_id', 'item_key'], 'wishlists_user_business_item_unique');
            $table->index(['business_id', 'user_id']);
            $table->index(['business_id', 'product_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
