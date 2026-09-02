<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill package_modules for Fixed Asset keys so existing packages are not
 * locked out (Package::moduleEnabled returns false when no row exists).
 */
return new class extends Migration
{
    public function up()
    {
        $keys = [
            'fixed-asset-category' => ['is_unlimited' => false, 'limit_value' => null],
            'fixed-asset' => ['is_unlimited' => true, 'limit_value' => null],
            'fixed-asset-depreciation' => ['is_unlimited' => false, 'limit_value' => null],
        ];

        $packages = DB::table('packages')->get();
        $now = now();

        foreach ($packages as $package) {
            $rows = [];

            foreach ($keys as $key => $limit_meta) {
                $rows[] = array_merge([
                    'package_id' => $package->package_id,
                    'module_key' => $key,
                    'is_enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $limit_meta);
            }

            DB::table('package_modules')->upsert(
                $rows,
                ['package_id', 'module_key'],
                ['is_enabled', 'is_unlimited', 'limit_value', 'updated_at']
            );
        }
    }

    public function down()
    {
        DB::table('package_modules')->whereIn('module_key', [
            'fixed-asset-category',
            'fixed-asset',
            'fixed-asset-depreciation',
        ])->delete();
    }
};
