<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('businesses') || !Schema::hasTable('business_intelligence_settings')) {
            return;
        }

        $now = now();
        $existing = DB::table('business_intelligence_settings')->pluck('business_id')->all();
        $businessIds = DB::table('businesses')->pluck('business_id')->all();

        $rows = [];
        foreach ($businessIds as $businessId) {
            if (in_array($businessId, $existing, true)) {
                continue;
            }
            $rows[] = [
                'business_id' => $businessId,
                'discount_change_threshold_percent' => 5,
                'voucher_change_threshold_percent' => 5,
                'complimentary_sales_percent_threshold' => 5,
                'high_discount_percent_threshold' => 15,
                'high_return_rate_percent' => 10,
                'high_cancellation_rate_percent' => 10,
                'dead_stock_days' => 90,
                'slow_moving_days' => 30,
                'delayed_order_hours' => 24,
                'offline_sync_stale_hours' => 24,
                'high_waste_percent_of_stock' => 2,
                'attendance_repeat_late_count' => 3,
                'low_margin_percent' => 10,
                'excellent_sales_growth_percent' => 20,
                'date_created' => $now,
                'date_updated' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('business_intelligence_settings')->insert($rows);
        }
    }

    public function down(): void
    {
        // Keep existing rows; dropping the table is handled by the create migration.
    }
};
