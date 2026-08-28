<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageModule;
use App\Support\Subscription\SubscriptionModuleRegistry;
use Illuminate\Database\Seeder;

/**
 * Seeds Dukanaz Intro website plans (PKR) with monthly + yearly pricing,
 * badge/tagline/compare marketing fields. Does not remove existing packages.
 *
 * Yearly amount = demo priceYearlyMonthly × 12 (annual total charged on yearly cycle).
 */
class IntroPackageCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->plans() as $plan) {
            $modules = $plan['modules'];
            unset($plan['modules']);

            $package = Package::updateOrCreate(
                ['name' => $plan['name'], 'duration_type' => 'monthly', 'is_deleted' => 0],
                $plan
            );

            $this->syncModules($package->package_id, $modules);
        }
    }

    protected function plans(): array
    {
        return [
            $this->starter(),
            $this->growth(),
            $this->business(),
            $this->enterprise(),
        ];
    }

    protected function starter(): array
    {
        return array_merge($this->base('Starter', 1), [
            'code' => 'NODE-01',
            'description' => 'For a single shop finding its footing.',
            'tagline' => 'For a single shop finding its footing.',
            'badge' => null,
            'best_for' => 'Single-branch retail, marts & small shops',
            'price' => 4500,
            'price_yearly' => 3600 * 12, // 43,200 PKR / year
            'features' => [
                'POS & Billing (barcode-ready)',
                'Inventory Management',
                'Sales & Purchases',
                'Customer Management',
                'Basic Reports (10+ reports)',
                'Website order sync',
            ],
            'limitations' => [
                'No HR & Payroll module',
                'No Automated Accounting',
                'No B2B Ordering Portal',
                'No API / custom integrations',
                'Email support only (48h response)',
            ],
            'compare' => [
                'accounting' => false,
                'hrPayroll' => false,
                'recurring' => false,
                'stockTransfers' => false,
                'b2bPortal' => false,
                'api' => false,
                'advancedReports' => false,
            ],
            'support' => 'Email support',
            'cta' => 'Choose Starter',
            'is_custom' => false,
            'modules' => [
                'umbrellas' => ['inventory', 'pos'],
                'unlimited' => [],
                'limits' => [
                    'branch' => 1,
                    'user' => 3,
                    'warehouse' => 1,
                    'product' => 750,
                    'order' => 1000,
                ],
            ],
        ]);
    }

    protected function growth(): array
    {
        return array_merge($this->base('Growth', 2), [
            'code' => 'NODE-02',
            'description' => 'For multi-branch operations scaling fast.',
            'tagline' => 'For multi-branch operations scaling fast.',
            'badge' => 'Most Provisioned',
            'best_for' => 'Growing retail chains, marts & small wholesalers',
            'price' => 9900,
            'price_yearly' => 7900 * 12, // 94,800 PKR / year
            'features' => [
                'Everything in Starter',
                'Automated Accounting',
                'HR & Payroll',
                'Recurring Transactions',
                'Stock Transfers between branches',
                'Discounts, Vouchers & Pricing Engine',
                'Advanced Reports & Analytics (40+)',
                'Priority chat & email support',
            ],
            'limitations' => [
                'No B2B Ordering Portal',
                'API access is read-only',
                'No dedicated onboarding specialist',
            ],
            'compare' => [
                'accounting' => true,
                'hrPayroll' => true,
                'recurring' => true,
                'stockTransfers' => true,
                'b2bPortal' => false,
                'api' => 'Read-only',
                'advancedReports' => true,
            ],
            'support' => 'Priority chat & email',
            'cta' => 'Choose Growth',
            'is_custom' => false,
            'modules' => [
                'umbrellas' => ['inventory', 'pos', 'accounting', 'hrm', 'payroll'],
                'unlimited' => [],
                'limits' => [
                    'branch' => 5,
                    'user' => 15,
                    'warehouse' => 3,
                    'product' => 6000,
                    'order' => 10000,
                ],
            ],
        ]);
    }

    protected function business(): array
    {
        return array_merge($this->base('Business', 3), [
            'code' => 'NODE-03',
            'description' => 'For wholesale, distribution & B2B operations.',
            'tagline' => 'For wholesale, distribution & B2B operations.',
            'badge' => null,
            'best_for' => 'Wholesale, distribution & B2B businesses',
            'price' => 18500,
            'price_yearly' => 14800 * 12, // 177,600 PKR / year
            'features' => [
                'Everything in Growth',
                'B2B Ordering Portal',
                'Full API & custom integrations',
                'Approval Workflows',
                'Multi-warehouse routing',
                'Granular roles & permissions',
                'Dedicated onboarding specialist',
                'Priority phone, chat & email support',
            ],
            'limitations' => [
                'Custom integration work billed separately',
                'Standard 99.5% uptime SLA',
            ],
            'compare' => [
                'accounting' => true,
                'hrPayroll' => true,
                'recurring' => true,
                'stockTransfers' => true,
                'b2bPortal' => true,
                'api' => true,
                'advancedReports' => true,
            ],
            'support' => 'Priority phone, chat & email',
            'cta' => 'Choose Business',
            'is_custom' => false,
            'modules' => [
                'umbrellas' => ['inventory', 'pos', 'accounting', 'hrm', 'payroll', 'service-management'],
                'unlimited' => ['product', 'order'],
                'limits' => [
                    'branch' => 15,
                    'user' => 50,
                    'warehouse' => 10,
                ],
            ],
        ]);
    }

    protected function enterprise(): array
    {
        return array_merge($this->base('Enterprise', 4), [
            'code' => 'NODE-∞',
            'description' => 'For unlimited scale on dedicated infrastructure.',
            'tagline' => 'For unlimited scale on dedicated infrastructure.',
            'badge' => 'Custom',
            'best_for' => 'Large multi-branch groups & enterprise distributors',
            'price' => null,
            'price_yearly' => null,
            'features' => [
                'Everything in Business',
                'Dedicated infrastructure',
                'Custom SLA & uptime guarantee',
                'White-glove data migration',
                'Custom modules & workflows',
                'Dedicated account manager',
            ],
            'limitations' => [
                'Annual contract required',
                'Onboarding timeline varies by scope',
                'Bank Transfer subscription requires a sales call first',
            ],
            'compare' => [
                'accounting' => true,
                'hrPayroll' => true,
                'recurring' => true,
                'stockTransfers' => true,
                'b2bPortal' => true,
                'api' => true,
                'advancedReports' => true,
            ],
            'support' => 'Dedicated account manager',
            'cta' => 'Talk to Sales',
            'is_custom' => true,
            'modules' => [
                'umbrellas' => ['inventory', 'pos', 'accounting', 'hrm', 'payroll', 'service-management'],
                'unlimited' => ['branch', 'user', 'warehouse', 'product', 'order', 'customer'],
                'limits' => [],
            ],
        ]);
    }

    protected function base(string $name, int $order): array
    {
        $existingId = Package::where('name', $name)
            ->where('duration_type', 'monthly')
            ->where('is_deleted', 0)
            ->value('package_id');

        $row = [
            'name' => $name,
            'currency' => 'PKR',
            'order' => $order,
            'duration_type' => 'monthly',
            'duration_days' => 30,
            'trial_days' => 0,
            'status' => 1,
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
