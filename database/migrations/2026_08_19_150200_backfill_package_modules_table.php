<?php

use App\Support\Subscription\SubscriptionModuleRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfills package_modules for every existing package from the 5 legacy
 * is_*_enabled boolean columns and 12 legacy max_* limit columns on
 * `packages`, so existing businesses keep exactly the module access/limits
 * they had before. Every newly-tracked module (no legacy column) inherits
 * its enabled state from its parent umbrella's legacy flag and gets the
 * spec-mandated default limit of 5. Safe to re-run (upsert on
 * package_id+module_key); does not modify the `packages` table itself.
 */
return new class extends Migration
{
    private const LEGACY_LIMIT_COLUMNS = [
        'branch' => 'max_branches',
        'user' => 'max_users',
        'customer' => 'max_customers',
        'warehouse' => 'max_warehouses',
        'category' => 'max_categories',
        'product' => 'max_products',
        'supplier' => 'max_suppliers',
        'purchase-request' => 'max_purchase_orders',
        'purchase' => 'max_purchases',
        'order' => 'max_sales',
        'transfer-note' => 'max_transfers',
        'expense' => 'max_expenses',
        'voucher' => 'max_vouchers',
    ];

    private const LEGACY_FEATURE_COLUMNS = [
        'pos' => 'is_pos_enabled',
        'inventory' => 'is_inventory_enabled',
        'accounting' => 'is_accounting_enabled',
        'hrm' => 'is_hrm_enabled',
        'payroll' => 'is_payroll_enabled',
    ];

    public function up()
    {
        $modules = SubscriptionModuleRegistry::modules();
        $packages = DB::table('packages')->get();
        $now = now();

        foreach ($packages as $package) {
            $rows = [];

            foreach ($modules as $key => $meta) {
                if ($meta['type'] === 'core') {
                    continue;
                }

                if (isset(self::LEGACY_FEATURE_COLUMNS[$key])) {
                    $isEnabled = (bool) $package->{self::LEGACY_FEATURE_COLUMNS[$key]};
                } elseif (isset(self::LEGACY_LIMIT_COLUMNS[$key])) {
                    $isEnabled = true; // numeric limits were never module-gated before
                } else {
                    $parent = $meta['parent'] ?? null;
                    if ($parent && isset(self::LEGACY_FEATURE_COLUMNS[$parent])) {
                        $isEnabled = (bool) $package->{self::LEGACY_FEATURE_COLUMNS[$parent]};
                    } else {
                        $isEnabled = $meta['default_enabled'] ?? true;
                    }
                }

                $isUnlimited = false;
                $limitValue = null;

                if ($meta['type'] === 'limited') {
                    if (isset(self::LEGACY_LIMIT_COLUMNS[$key])) {
                        $raw = (float) $package->{self::LEGACY_LIMIT_COLUMNS[$key]};
                        if ($raw == -1.0) {
                            $isUnlimited = true;
                        } else {
                            $limitValue = (int) $raw;
                        }
                    } else {
                        $limitValue = $meta['default_limit'] ?? 5;
                    }
                }

                $rows[] = [
                    'package_id' => $package->package_id,
                    'module_key' => $key,
                    'is_enabled' => $isEnabled,
                    'is_unlimited' => $isUnlimited,
                    'limit_value' => $limitValue,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 50) as $chunk) {
                DB::table('package_modules')->upsert(
                    $chunk,
                    ['package_id', 'module_key'],
                    ['is_enabled', 'is_unlimited', 'limit_value', 'updated_at']
                );
            }
        }
    }

    public function down()
    {
        DB::table('package_modules')->truncate();
    }
};
