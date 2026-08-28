<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform-level Dukanaz Intro/marketing CMS (Super Admin).
 * Separate from per-business storefront Website* tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intro_modules', function (Blueprint $table) {
            $table->uuid('intro_module_id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->string('category')->nullable()->index();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->string('status')->default('active')->index();
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });

        Schema::create('intro_blog_categories', function (Blueprint $table) {
            $table->uuid('intro_blog_category_id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status')->default('active')->index();
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });

        Schema::create('intro_blog_tags', function (Blueprint $table) {
            $table->uuid('intro_blog_tag_id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active')->index();
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });

        Schema::create('intro_blogs', function (Blueprint $table) {
            $table->uuid('intro_blog_id')->primary();
            $table->uuid('intro_blog_category_id')->nullable()->index();
            $table->unsignedBigInteger('author_id')->nullable()->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('featured_image')->nullable();
            $table->unsignedInteger('reading_time')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->boolean('is_featured')->default(false);
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });

        Schema::create('intro_blog_tag', function (Blueprint $table) {
            $table->uuid('intro_blog_id');
            $table->uuid('intro_blog_tag_id');
            $table->primary(['intro_blog_id', 'intro_blog_tag_id']);
        });

        Schema::create('intro_blog_comments', function (Blueprint $table) {
            $table->uuid('intro_blog_comment_id')->primary();
            $table->uuid('intro_blog_id')->index();
            $table->string('name');
            $table->string('email');
            $table->text('comment');
            $table->string('status')->default('pending')->index();
            $table->text('moderation_note')->nullable();
            $table->integer('moderatedby_id')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });

        Schema::create('intro_testimonials', function (Blueprint $table) {
            $table->uuid('intro_testimonial_id')->primary();
            $table->string('business_name')->nullable();
            $table->string('customer_name');
            $table->string('designation')->nullable();
            $table->string('business_type')->nullable();
            $table->text('review_text');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('image')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status')->default('active')->index();
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });

        Schema::create('intro_contact_inquiries', function (Blueprint $table) {
            $table->uuid('intro_contact_inquiry_id')->primary();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status')->default('new')->index();
            $table->boolean('is_deleted')->default(false);
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });

        Schema::create('intro_contact_replies', function (Blueprint $table) {
            $table->uuid('intro_contact_reply_id')->primary();
            $table->uuid('intro_contact_inquiry_id')->index();
            $table->text('reply_message');
            $table->string('send_status')->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('repliedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
        });

        Schema::create('intro_website_settings', function (Blueprint $table) {
            $table->uuid('intro_website_setting_id')->primary();
            $table->string('group')->default('general')->index();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text');
            $table->string('label')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->timestamp('date_updated')->nullable();
        });

        Schema::create('intro_navigation_items', function (Blueprint $table) {
            $table->uuid('intro_navigation_item_id')->primary();
            $table->string('label');
            $table->string('url')->nullable();
            $table->string('section_key')->nullable();
            $table->string('match_key')->nullable();
            $table->string('location')->default('header')->index();
            $table->uuid('parent_id')->nullable()->index();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status')->default('active')->index();
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });

        Schema::create('intro_media', function (Blueprint $table) {
            $table->uuid('intro_media_id')->primary();
            $table->string('filename');
            $table->string('original_name')->nullable();
            $table->string('disk_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('collection')->default('general')->index();
            $table->string('alt_text')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });

        Schema::create('intro_homepage_sections', function (Blueprint $table) {
            $table->uuid('intro_homepage_section_id')->primary();
            $table->string('section_key')->unique();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->longText('content')->nullable();
            $table->json('content_json')->nullable();
            $table->string('image')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_link')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->string('status')->default('active')->index();
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });

        Schema::create('intro_pages', function (Blueprint $table) {
            $table->uuid('intro_page_id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });

        Schema::create('intro_business_registrations', function (Blueprint $table) {
            $table->uuid('intro_business_registration_id')->primary();
            $table->uuid('business_id')->nullable()->index();
            $table->uuid('package_id')->nullable()->index();
            $table->string('billing_cycle')->nullable();
            $table->string('business_name');
            $table->string('owner_name');
            $table->string('owner_email');
            $table->string('owner_phone')->nullable();
            $table->string('business_email')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('business_type')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index();
            $table->json('meta')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intro_business_registrations');
        Schema::dropIfExists('intro_pages');
        Schema::dropIfExists('intro_homepage_sections');
        Schema::dropIfExists('intro_media');
        Schema::dropIfExists('intro_navigation_items');
        Schema::dropIfExists('intro_website_settings');
        Schema::dropIfExists('intro_contact_replies');
        Schema::dropIfExists('intro_contact_inquiries');
        Schema::dropIfExists('intro_testimonials');
        Schema::dropIfExists('intro_blog_comments');
        Schema::dropIfExists('intro_blog_tag');
        Schema::dropIfExists('intro_blogs');
        Schema::dropIfExists('intro_blog_tags');
        Schema::dropIfExists('intro_blog_categories');
        Schema::dropIfExists('intro_modules');
    }
};
