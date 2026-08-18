<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-business Notification Settings tab (15th table in the existing
     * Setting-module family - see SettingService/SettingController).
     * Low-stock alerting intentionally reuses the existing
     * inventory_settings.low_stock_alert / low_stock_quantity columns
     * rather than duplicating them here.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->nullable();

            $table->boolean('payment_due_alert_enabled')->default(true);
            $table->integer('payment_due_days_before')->default(3);

            $table->boolean('credit_limit_alert_enabled')->default(true);
            $table->integer('credit_limit_threshold_percent')->default(100);

            $table->boolean('supplier_payment_reminder_enabled')->default(true);
            $table->integer('supplier_payment_reminder_days_before')->default(3);

            $table->boolean('order_status_alert_enabled')->default(true);

            $table->boolean('sound_enabled')->default(true);

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
        Schema::dropIfExists('notification_settings');
    }
};
