<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Super-Admin-controlled, whole-business platform access switches (Business
     * Access Control). Default true so every business keeps working as today
     * unless a Super Admin explicitly blocks a platform - blocking is strictly
     * business-level, applying automatically to every branch/user, never
     * configured per-branch or per-user. Website and Mobile App share a single
     * flag (storefront_access_enabled) because they share the exact same
     * /api/mobile route surface today with no client-identifying signal.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('erp_access_enabled')->default(true)->after('status');
            $table->boolean('storefront_access_enabled')->default(true)->after('erp_access_enabled');
            $table->boolean('pos_access_enabled')->default(true)->after('storefront_access_enabled');
            $table->boolean('offline_pos_access_enabled')->default(true)->after('pos_access_enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['erp_access_enabled', 'storefront_access_enabled', 'pos_access_enabled', 'offline_pos_access_enabled']);
        });
    }
};
