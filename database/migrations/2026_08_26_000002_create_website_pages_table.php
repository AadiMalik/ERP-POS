<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Long-form static content pages: Privacy Policy, Terms & Conditions,
 * Shipping Information, Cancellation Policy, Return Policy. Slug-addressed
 * per business, with page-level SEO (site-wide SEO already lives on
 * website_theme_settings - this is for per-page overrides).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_pages', function (Blueprint $table) {
            $table->uuid('page_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->string('slug');
            $table->string('title')->nullable();
            $table->longText('content')->nullable();

            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->string('og_image')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_pages');
    }
};
