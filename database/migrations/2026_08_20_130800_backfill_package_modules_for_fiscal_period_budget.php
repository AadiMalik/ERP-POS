<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Package::moduleEnabled() returns false when a package has no
 * package_modules row at all for a key (see
 * 2026_08_19_150200_backfill_package_modules_table.php for the original
 * backfill). fiscal-year / accounting-period / budget are new
 * SubscriptionModuleRegistry keys added alongside this feature, so every
 * package that already existed before this migration has no row for them
 * yet - this backfills one, enabled by default (matching each key's
 * 'default_enabled' => true in the registry), so existing businesses aren't
 * silently locked out of Advanced Accounting Mode's Role Create/Edit
 * permission matrix the moment they upgrade. Safe to re-run (upsert on
 * package_id+module_key).
 */
return new class extends Migration
{
    public function up()
    {
        $keys = [
            'fiscal-year'       => ['is_unlimited' => false, 'limit_value' => null],
            'accounting-period' => ['is_unlimited' => false, 'limit_value' => null],
            'budget'            => ['is_unlimited' => false, 'limit_value' => 5],
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
        DB::table('package_modules')->whereIn('module_key', ['fiscal-year', 'accounting-period', 'budget'])->delete();
    }
};
