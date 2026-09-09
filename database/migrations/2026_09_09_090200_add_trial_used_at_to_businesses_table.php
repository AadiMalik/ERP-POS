<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Set once, the first (and only) time a business's package assignment is
     * a trial package (trial_days > 0) - checked before ever assigning a
     * trial package to that business again, on upgrade/downgrade/renewal,
     * for the lifetime of the business.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->timestamp('trial_used_at')->nullable()->after('offline_pos_access_enabled');
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
            $table->dropColumn('trial_used_at');
        });
    }
};
