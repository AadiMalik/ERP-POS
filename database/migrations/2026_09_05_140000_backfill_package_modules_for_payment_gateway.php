<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill package_modules for the 'payment-gateway'/'payment-transaction'
 * keys so existing packages are not locked out (Package::moduleEnabled
 * returns false when no row exists for a package_id+module_key, regardless
 * of the registry's default_enabled). These keys were added to
 * SubscriptionModuleRegistry by the Payment Gateway Integration Framework
 * but every package already in the database predates them.
 */
return new class extends Migration
{
    private const MODULE_KEYS = ['payment-gateway', 'payment-transaction'];

    public function up()
    {
        $packages = DB::table('packages')->get();
        $now = now();

        $rows = [];
        foreach ($packages as $package) {
            foreach (self::MODULE_KEYS as $module_key) {
                $rows[] = [
                    'package_id' => $package->package_id,
                    'module_key' => $module_key,
                    'is_enabled' => true,
                    'is_unlimited' => false,
                    'limit_value' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
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
        DB::table('package_modules')->whereIn('module_key', self::MODULE_KEYS)->delete();
    }
};
