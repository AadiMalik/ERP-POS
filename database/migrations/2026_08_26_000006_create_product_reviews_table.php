<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->uuid('review_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('reviewer_name')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();

            // Published by default - admin can hide (unpublish) later.
            $table->enum('status', ['published', 'hidden'])->default('published');
            $table->boolean('is_deleted')->default(false);
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
