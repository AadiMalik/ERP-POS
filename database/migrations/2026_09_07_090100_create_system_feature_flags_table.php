<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Super-Admin, platform-wide on/off switches for system features,
     * services, and integrations (e.g. push notifications, an online
     * payment gateway family) - independent of the per-business Package
     * module-tier gating (FeatureLimitService/module: middleware) and of
     * per-business platform access (see
     * 2026_09_07_090000_add_platform_access_to_businesses_table.php). A
     * developer-registered registry, not user-created data, so no soft
     * delete - rows are seeded/upserted by key via SystemFeatureFlagSeeder.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_feature_flags', function (Blueprint $table) {
            $table->uuid('system_feature_flag_id')->primary();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_enabled')->default(true);

            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_feature_flags');
    }
};
