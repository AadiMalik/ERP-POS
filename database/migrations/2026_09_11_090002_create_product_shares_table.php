<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only log of every "share this product" click from the website
     * or mobile app - one row per share, so both the total count and a
     * per-platform breakdown can be computed without any denormalized
     * per-platform columns (a new platform never needs a schema change).
     * customer_id is nullable - a guest shopper's share is still logged,
     * just without an identity (see App\Services\Concrete\Api\ProductShareService).
     */
    public function up()
    {
        Schema::create('product_shares', function (Blueprint $table) {
            $table->uuid('product_share_id')->primary();
            $table->uuid('business_id');
            $table->uuid('product_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('platform', 40);
            $table->timestamp('date_created')->nullable();

            $table->foreign('business_id')->references('business_id')->on('businesses')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['product_id', 'platform']);
            $table->index(['business_id', 'date_created']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_shares');
    }
};
