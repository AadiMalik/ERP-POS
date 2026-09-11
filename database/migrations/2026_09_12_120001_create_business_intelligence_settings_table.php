<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_intelligence_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('business_id')->unique();
            $table->decimal('discount_change_threshold_percent', 8, 2)->default(5);
            $table->decimal('voucher_change_threshold_percent', 8, 2)->default(5);
            $table->decimal('complimentary_sales_percent_threshold', 8, 2)->default(5);
            $table->decimal('high_discount_percent_threshold', 8, 2)->default(15);
            $table->decimal('high_return_rate_percent', 8, 2)->default(10);
            $table->decimal('high_cancellation_rate_percent', 8, 2)->default(10);
            $table->unsignedInteger('dead_stock_days')->default(90);
            $table->unsignedInteger('slow_moving_days')->default(30);
            $table->unsignedInteger('delayed_order_hours')->default(24);
            $table->unsignedInteger('offline_sync_stale_hours')->default(24);
            $table->decimal('high_waste_percent_of_stock', 8, 2)->default(2);
            $table->unsignedInteger('attendance_repeat_late_count')->default(3);
            $table->decimal('low_margin_percent', 8, 2)->default(10);
            $table->decimal('excellent_sales_growth_percent', 8, 2)->default(20);
            $table->uuid('createdby_id')->nullable();
            $table->uuid('updatedby_id')->nullable();
            $table->dateTime('date_created')->nullable();
            $table->dateTime('date_updated')->nullable();

            $table->foreign('business_id')->references('business_id')->on('businesses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_intelligence_settings');
    }
};
