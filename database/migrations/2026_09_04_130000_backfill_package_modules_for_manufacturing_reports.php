<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill package_modules for the 'manufacturing-reports' key so existing
 * packages are not locked out (Package::moduleEnabled returns false when no
 * row exists). This key was added to SubscriptionModuleRegistry alongside
 * 'manufacturing'/'recipe'/'manufacturing-plan'/'production' but was missed
 * by 2026_09_04_090600_backfill_package_modules_for_manufacturing.php.
 */
return new class extends Migration
{
    public function up()
    {
        $packages = DB::table('packages')->get();
        $now = now();

        $rows = [];
        foreach ($packages as $package) {
            $rows[] = [
                'package_id' => $package->package_id,
                'module_key' => 'manufacturing-reports',
                'is_enabled' => true,
                'is_unlimited' => false,
                'limit_value' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            DB::table('package_modules')->upsert(
                $rows,
                ['package_id', 'module_key'],
                ['is_enabled', 'is_unlimited', 'limit_value', 'updated_at']
            );
        }
    }

    public function down()
    {
        DB::table('package_modules')->where('module_key', 'manufacturing-reports')->delete();
    }
};
