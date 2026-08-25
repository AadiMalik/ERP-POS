<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('website_theme_settings', function (Blueprint $table) {
            $table->string('favicon')->nullable()->after('typography_style');
            $table->string('business_hours')->nullable()->after('favicon');

            $table->string('seo_title')->nullable()->after('business_hours');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->text('seo_keywords')->nullable()->after('seo_description');
            $table->string('og_image')->nullable()->after('seo_keywords');

            $table->string('whatsapp_number')->nullable()->after('og_image');
            $table->json('social_links')->nullable()->after('whatsapp_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('website_theme_settings', function (Blueprint $table) {
            $table->dropColumn([
                'favicon',
                'business_hours',
                'seo_title',
                'seo_description',
                'seo_keywords',
                'og_image',
                'whatsapp_number',
                'social_links',
            ]);
        });
    }
};
