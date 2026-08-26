<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_theme_settings', function (Blueprint $table) {
            $table->boolean('free_delivery_enabled')->default(false)->after('social_links');
            $table->decimal('free_delivery_min_amount', 10, 2)->nullable()->after('free_delivery_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('website_theme_settings', function (Blueprint $table) {
            $table->dropColumn(['free_delivery_enabled', 'free_delivery_min_amount']);
        });
    }
};
