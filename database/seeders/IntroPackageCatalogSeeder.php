<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageModule;
use App\Support\Subscription\SubscriptionModuleRegistry;
use Illuminate\Database\Seeder;

/**
 * Seeds Dukanaz catalog: Starter / Growth / Business / Enterprise × monthly + yearly.
 *
 * Yearly list price = monthly × 12; yearly rows use discount = 10%.
 * Module access comes from package_modules (not marketing features JSON).
 */
class IntroPackageCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->plans() as $plan) {
            $modules = $plan['modules'];
            unset($plan['modules']);

            $package = Package::updateOrCreate(
                [
                    'name' => $plan['name'],
                    'duration_type' => $plan['duration_type'],
                    'is_deleted' => 0,
                ],
                $plan
            );

            $this->syncModules($package->package_id, $modules);
        }

        // Legacy USD / Professional catalog rows — keep data but hide from pricing.
        Package::where('is_deleted', 0)
            ->whereIn('name', ['Professional', 'Basic Plan'])
            ->update(['status' => 0, 'date_updated' => now()]);
    }

    protected function plans(): array
    {
        $out = [];
        foreach (['monthly', 'yearly'] as $duration) {
            $out[] = $this->starter($duration);
            $out[] = $this->growth($duration);
            $out[] = $this->business($duration);
            $out[] = $this->enterprise($duration);
        }

        return $out;
    }

    protected function starter(string $duration): array
    {
        return array_merge($this->priced('Starter', 1, 4500, $duration), [
            'code' => $duration === 'yearly' ? 'NODE-01-Y' : 'NODE-01',
            'description' => 'For a single shop finding its footing.',
            'tagline' => 'For a single shop finding its footing.',
            'badge' => null,
            'best_for' => 'Single-branch retail, marts & small shops',
            'support' => 'Email support',
            'cta' => 'Choose Starter',
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

    protected function growth(string $duration): array
    {
        return array_merge($this->priced('Growth', 2, 9900, $duration), [
            'code' => $duration === 'yearly' ? 'NODE-02-Y' : 'NODE-02',
            'description' => 'For multi-branch operations scaling fast.',
            'tagline' => 'For multi-branch operations scaling fast.',
            'badge' => 'Most Provisioned',
            'best_for' => 'Growing retail chains, marts & small wholesalers',
            'support' => 'Priority chat & email',
            'cta' => 'Choose Growth',
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

    protected function business(string $duration): array
    {
        return array_merge($this->priced('Business', 3, 18500, $duration), [
            'code' => $duration === 'yearly' ? 'NODE-03-Y' : 'NODE-03',
            'description' => 'For wholesale, distribution & B2B operations.',
            'tagline' => 'For wholesale, distribution & B2B operations.',
            'badge' => null,
            'best_for' => 'Wholesale, distribution & B2B businesses',
            'support' => 'Priority phone, chat & email',
            'cta' => 'Choose Business',
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

    protected function enterprise(string $duration): array
    {
        return array_merge($this->priced('Enterprise', 4, 35000, $duration), [
            'code' => $duration === 'yearly' ? 'NODE-ENT-Y' : 'NODE-ENT',
            'description' => 'For unlimited scale on dedicated infrastructure.',
            'tagline' => 'For unlimited scale on dedicated infrastructure.',
            'badge' => 'Enterprise',
            'best_for' => 'Large multi-branch groups & enterprise distributors',
            'support' => 'Dedicated account manager',
            'cta' => 'Choose Enterprise',
            'modules' => [
                'umbrellas' => ['inventory', 'pos', 'accounting', 'hrm', 'payroll', 'service-management'],
                'unlimited' => ['branch', 'user', 'warehouse', 'product', 'order', 'customer'],
                'limits' => [],
            ],
        ]);
    }

    /**
     * @param  float  $monthlyPrice  Base monthly list price (PKR)
     */
    protected function priced(string $name, int $order, float $monthlyPrice, string $duration): array
    {
        $isYearly = $duration === 'yearly';
        $listPrice = $isYearly ? $monthlyPrice * 12 : $monthlyPrice;
        $discount = $isYearly ? 10 : 0;

        $existingId = Package::where('name', $name)
            ->where('duration_type', $duration)
            ->where('is_deleted', 0)
            ->value('package_id');

        $row = [
            'name' => $name,
            'currency' => 'PKR',
            'price' => $listPrice,
            'discount' => $discount,
            'price_yearly' => null,
            'features' => null,
            'limitations' => null,
            'compare' => null,
            'is_custom' => false,
            'order' => $order,
            'duration_type' => $duration,
            'duration_days' => $isYearly ? 365 : 30,
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
