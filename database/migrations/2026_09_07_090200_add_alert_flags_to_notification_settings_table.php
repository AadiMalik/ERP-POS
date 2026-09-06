<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New Order alert (ERP) and the "Website/Mobile App Order Notification to
     * POS" setting - both default true so nothing changes for a business
     * until they choose to turn one off.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->boolean('new_order_alert_enabled')->default(true)->after('order_status_alert_enabled');
            $table->boolean('website_order_notify_pos_enabled')->default(true)->after('new_order_alert_enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn(['new_order_alert_enabled', 'website_order_notify_pos_enabled']);
        });
    }
};
