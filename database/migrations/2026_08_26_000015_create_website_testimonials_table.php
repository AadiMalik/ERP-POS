<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Curated marketing testimonials shown on the homepage - a distinct concept
 * from product_reviews (verified per-product reviews). Same repeatable-card
 * shape/conventions as website_hero_stats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_testimonials', function (Blueprint $table) {
            $table->uuid('testimonial_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->string('author_name');
            $table->string('author_title')->nullable();
            $table->string('avatar')->nullable();
            $table->text('quote');
            $table->tinyInteger('rating')->nullable();
            $table->integer('sort_order')->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_testimonials');
    }
};
