<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generalizes website_benefits from a single "Why Shop With Us" list into a
 * reusable repeatable-card list for every context that needs the same
 * icon/title/description shape (product trust badges, cart trust badges,
 * login/signup promo bullets, about-page values, delivery options, payment
 * method blurbs, payment icons, announcement bar messages) - same pattern
 * already used by website_sections.type, avoiding a new near-identical table
 * per context. Existing rows default to 'why_shop_with_us' so nothing about
 * today's homepage benefits changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_benefits', function (Blueprint $table) {
            $table->string('group')->default('why_shop_with_us')->after('business_id');
            $table->string('value')->nullable()->after('description');
            $table->string('code')->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('website_benefits', function (Blueprint $table) {
            $table->dropColumn(['group', 'value', 'code']);
        });
    }
};
