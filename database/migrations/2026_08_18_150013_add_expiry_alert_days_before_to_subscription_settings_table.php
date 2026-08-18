<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinct from reminder_thresholds_days (which drives the existing
     * multi-touch email/SMS renewal campaign) - this single value drives
     * the new in-app "Subscription Expiry Alert" bell notification, sent to
     * both Business Admin and Super Admin.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_settings', function (Blueprint $table) {
            $table->integer('expiry_alert_days_before')->default(5)->after('reminder_thresholds_days');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_settings', function (Blueprint $table) {
            $table->dropColumn('expiry_alert_days_before');
        });
    }
};
