<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill package_modules for 'analytics' so existing packages are not
 * locked out (Package::moduleEnabled returns false when no row exists).
 * Same pattern as payment-gateway / manufacturing backfills.
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
                'module_key' => 'analytics',
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
        DB::table('package_modules')->where('module_key', 'analytics')->delete();
    }
};
