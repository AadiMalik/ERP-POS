<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated Social Media CRUD - supersedes the fixed-key
 * website_theme_settings.social_links JSON blob (which only supported a
 * hardcoded platform list with no per-item ordering/status/color). That
 * column is left in place for backward compatibility but the storefront
 * should prefer this table going forward.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_links', function (Blueprint $table) {
            $table->uuid('social_media_link_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->string('platform');
            $table->string('url');
            $table->string('icon')->nullable();
            $table->string('icon_color')->nullable();
            $table->string('display_color')->nullable();
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
        Schema::dropIfExists('social_media_links');
    }
};
