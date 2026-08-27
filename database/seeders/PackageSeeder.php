<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageModule;
use App\Support\Subscription\SubscriptionModuleRegistry;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Idempotent catalog of the three public plans. Does not delete or
     * reassign any existing package (e.g. Basic Plan) — Super Admin can
     * deactivate extras so the price table shows only these tiers.
     */
    public function run()
    {
        foreach ($this->plans() as $plan) {
            $modules = $plan['modules'];
            unset($plan['modules']);

            $package = Package::updateOrCreate(
                ['name' => $plan['name'], 'is_deleted' => 0],
                $plan
            );

            $this->syncModules($package->package_id, $modules);
        }
    }

    protected function plans(): array
    {
        return [
            $this->starter(),
            $this->professional(),
            $this->enterprise(),
        ];
    }

    protected function starter(): array
    {
        return array_merge($this->baseRow(
            'Starter',
            'For small shops getting started with inventory and point of sale.',
            49,
            1,
            ['inventory' => true, 'pos' => true]
        ), [
            'modules' => [
                'umbrellas' => ['inventory', 'pos'],
                'unlimited' => [],
                'limits' => [
                    'branch' => 1,
                    'user' => 2,
                    'warehouse' => 1,
                    'brand' => 10,
                    'category' => 10,
                    'sub-category' => 20,
                    'product' => 50,
                    'product-variation' => 50,
                    'stock-taking' => 5,
                    'transfer-note' => 10,
                    'supplier' => 10,
                    'purchase-request' => 25,
                    'purchase-request-quotation' => 25,
                    'purchase' => 50,
                    'good-receipt-note' => 50,
                    'purchase-return' => 25,
                    'supplier-payment' => 50,
                    'payment-method' => 3,
                    'discount' => 5,
                    'customer' => 50,
                    'order' => 100,
                ],
            ],
        ]);
    }

    protected function professional(): array
    {
        return array_merge($this->baseRow(
            'Professional',
            'For growing businesses that need accounting and service billing alongside inventory.',
            149,
            2,
            ['inventory' => true, 'pos' => true, 'accounting' => true, 'service-management' => true]
        ), [
            'modules' => [
                'umbrellas' => ['inventory', 'pos', 'accounting', 'service-management'],
                'unlimited' => [],
                'limits' => [
                    'branch' => 3,
                    'user' => 10,
                    'warehouse' => 3,
                    'brand' => 50,
                    'category' => 50,
                    'sub-category' => 100,
                    'product' => 500,
                    'product-variation' => 500,
                    'stock-taking' => 25,
                    'transfer-note' => 50,
                    'supplier' => 50,
                    'purchase-request' => 200,
                    'purchase-request-quotation' => 200,
                    'purchase' => 500,
                    'good-receipt-note' => 500,
                    'purchase-return' => 200,
                    'supplier-payment' => 500,
                    'account' => 100,
                    'journal-entry' => 500,
                    'recurring-transaction' => 25,
                    'voucher' => 500,
                    'budget' => 10,
                    'expense' => 200,
                    'expense-category' => 20,
                    'admin-expense' => 50,
                    'service-purchase' => 200,
                    'service-purchase-return' => 50,
                    'service-sale' => 200,
                    'service-sale-return' => 50,
                    'payment-method' => 10,
                    'discount' => 25,
                    'customer' => 500,
                    'order' => 1000,
                ],
            ],
        ]);
    }

    protected function enterprise(): array
    {
        return array_merge($this->baseRow(
            'Enterprise',
            'Full ERP for larger operations: HRM, payroll, and high or unlimited usage caps.',
            349,
            3,
            ['inventory' => true, 'pos' => true, 'accounting' => true, 'service-management' => true, 'hrm' => true, 'payroll' => true]
        ), [
            'modules' => [
                'umbrellas' => ['inventory', 'pos', 'accounting', 'service-management', 'hrm', 'payroll'],
                'unlimited' => ['product', 'product-variation', 'customer', 'order', 'brand', 'category', 'sub-category'],
                'limits' => [
                    'branch' => 10,
                    'user' => 50,
                    'warehouse' => 10,
                    'stock-taking' => 100,
                    'transfer-note' => 200,
                    'supplier' => 200,
                    'purchase-request' => 1000,
                    'purchase-request-quotation' => 1000,
                    'purchase' => 2000,
                    'good-receipt-note' => 2000,
                    'purchase-return' => 500,
                    'supplier-payment' => 2000,
                    'account' => 500,
                    'journal-entry' => 5000,
                    'recurring-transaction' => 100,
                    'voucher' => 2000,
                    'budget' => 50,
                    'expense' => 1000,
                    'expense-category' => 50,
                    'admin-expense' => 200,
                    'service-purchase' => 1000,
                    'service-purchase-return' => 200,
                    'service-sale' => 1000,
                    'service-sale-return' => 200,
                    'payment-method' => 25,
                    'discount' => 100,
                    'department' => 20,
                    'designation' => 30,
                    'shift' => 10,
                    'employee' => 100,
                    'payroll' => 24,
                ],
            ],
        ]);
    }

    protected function baseRow(string $name, string $description, float $price, int $order, array $umbrellas): array
    {
        $existingId = Package::where('name', $name)->where('is_deleted', 0)->value('package_id');

        $row = [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'order' => $order,
            'duration_type' => 'monthly',
            'duration_days' => 30,
            'trial_days' => 0,
            'status' => 1,
            'is_inventory_enabled' => $umbrellas['inventory'] ?? false,
            'is_pos_enabled' => $umbrellas['pos'] ?? false,
            'is_accounting_enabled' => $umbrellas['accounting'] ?? false,
            'is_hrm_enabled' => $umbrellas['hrm'] ?? false,
            'is_payroll_enabled' => $umbrellas['payroll'] ?? false,
            'is_deleted' => 0,
            'date_updated' => now(),
        ];

        if ($existingId) {
            $row['package_id'] = $existingId;
        } else {
            $row['package_id'] = generateUuid();
            $row['date_created'] = now();
        }

        return $row;
    }

    /**
     * One package_modules row per gated SubscriptionModuleRegistry key,
     * matching PackageService::saveModules().
     */
    protected function syncModules(string $packageId, array $config): void
    {
        $umbrellas = $config['umbrellas'] ?? [];
        $limits = $config['limits'] ?? [];
        $unlimited = $config['unlimited'] ?? [];
        $umbrellaKeys = ['inventory', 'pos', 'accounting', 'hrm', 'payroll', 'service-management'];

        foreach (SubscriptionModuleRegistry::modules() as $key => $meta) {
            if ($meta['type'] === 'core') {
                continue;
            }

            $parent = $meta['parent'] ?? null;
            $isUmbrella = in_array($key, $umbrellaKeys, true);

            if ($isUmbrella) {
                $isEnabled = in_array($key, $umbrellas, true);
            } elseif (in_array($key, ['branch', 'user'], true)) {
                $isEnabled = true;
            } elseif ($parent) {
                $isEnabled = in_array($parent, $umbrellas, true);
            } else {
                $isEnabled = (bool) ($meta['default_enabled'] ?? false);
            }

            $unlimitedAllowed = $meta['unlimited_allowed'] ?? false;
            $isUnlimited = $isEnabled && $unlimitedAllowed && in_array($key, $unlimited, true);
            $limitValue = null;

            if ($meta['type'] === 'limited' && $isEnabled && !$isUnlimited) {
                $limitValue = $limits[$key] ?? ($meta['default_limit'] ?? 5);
            }

            PackageModule::updateOrCreate(
                ['package_id' => $packageId, 'module_key' => $key],
                [
                    'is_enabled' => $isEnabled,
                    'is_unlimited' => $isUnlimited,
                    'limit_value' => $limitValue,
                ]
            );
        }
    }
}
