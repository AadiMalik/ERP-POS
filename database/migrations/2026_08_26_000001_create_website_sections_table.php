<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generic CMS content block for every homepage/website section that shares
 * the same shape (heading, description, optional icon, image(s), button,
 * link target, ordering, status): Hero, About Us, Contact Us (intro copy —
 * actual contact details still come from businesses/branches), Why Shop
 * With Us, promo/discount banners, and the CMS-controlled wrapper around
 * each product-group homepage section (Featured/Discounted/Trending/New
 * Arrivals/Best Sellers) whose actual products come from the Products API.
 * One table, keyed by `type`, instead of a table per section — avoids
 * duplicating this shape 8+ times while still letting the frontend hide any
 * section that has no valid content.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_sections', function (Blueprint $table) {
            $table->uuid('section_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->string('type');

            $table->string('heading')->nullable();
            $table->string('heading_icon')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('image_mobile')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_link')->nullable();
            // Banner destination: product / category / collection / shop / promotion / custom
            $table->string('link_type')->nullable();
            $table->string('link_target_id')->nullable();

            $table->integer('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_sections');
    }
};
