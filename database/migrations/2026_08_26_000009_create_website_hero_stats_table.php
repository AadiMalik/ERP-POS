<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repeatable Hero Stats/Highlights shown in the storefront Hero section
 * (e.g. "12k+ Happy Customers"). Same shape as social_media_links -
 * per-item icon/color, sort_order, status, soft delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_hero_stats', function (Blueprint $table) {
            $table->uuid('hero_stat_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->string('value');
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('icon_color')->nullable();
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
        Schema::dropIfExists('website_hero_stats');
    }
};
