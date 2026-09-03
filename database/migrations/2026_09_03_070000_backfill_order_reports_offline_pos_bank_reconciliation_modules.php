<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfills package_modules rows for three module keys that were added to
 * SubscriptionModuleRegistry after existing packages were already synced:
 * 'order-reports', 'offline-pos' (both parent: pos), and 'bank-reconciliation'
 * (parent: accounting, registered earlier but never backfilled). Without this,
 * FeatureLimitService::hasModule() returns false for any package missing the
 * row - a silent regression for every existing business. Each new key
 * inherits its parent umbrella's already-synced enabled state. Safe to
 * re-run (upsert on package_id+module_key).
 */
return new class extends Migration
{
    private const NEW_KEYS = [
        'order-reports' => 'pos',
        'offline-pos' => 'pos',
        'bank-reconciliation' => 'accounting',
    ];

    public function up()
    {
        $packages = DB::table('packages')->get(['package_id']);
        $now = now();

        foreach ($packages as $package) {
            $parentStates = DB::table('package_modules')
                ->where('package_id', $package->package_id)
                ->whereIn('module_key', array_unique(array_values(self::NEW_KEYS)))
                ->pluck('is_enabled', 'module_key');

            $rows = [];

            foreach (self::NEW_KEYS as $key => $parent) {
                // Row already exists (e.g. bank-reconciliation on a freshly-seeded
                // catalog package) - leave it exactly as it is.
                $exists = DB::table('package_modules')
                    ->where('package_id', $package->package_id)
                    ->where('module_key', $key)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $rows[] = [
                    'package_id' => $package->package_id,
                    'module_key' => $key,
                    'is_enabled' => (bool) ($parentStates[$parent] ?? false),
                    'is_unlimited' => false,
                    'limit_value' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows) {
                DB::table('package_modules')->upsert(
                    $rows,
                    ['package_id', 'module_key'],
                    ['is_enabled', 'is_unlimited', 'limit_value', 'updated_at']
                );
            }
        }
    }

    public function down()
    {
        DB::table('package_modules')->whereIn('module_key', array_keys(self::NEW_KEYS))->delete();
    }
};
