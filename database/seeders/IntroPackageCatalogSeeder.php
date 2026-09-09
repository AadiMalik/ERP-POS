<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageModule;
use App\Support\Subscription\SubscriptionModuleRegistry;
use Illuminate\Database\Seeder;

/**
 * Seeds the commercial catalog: Free Trial (once, 30 days, never repeatable -
 * see Business.trial_used_at / SubscriptionService) + Starter / Business /
 * Professional / Enterprise, each as monthly + yearly rows (yearly = monthly
 * x12 list price with a 10% discount baked in via Package::effectivePrice()).
 *
 * Every `limited` SubscriptionModuleRegistry module gets an explicit
 * package_modules row (is_enabled, is_unlimited, limit_value, limit_type) -
 * Enterprise is unlimited across every one of them, no exceptions.
 *
 * Idempotent: Package rows are keyed on (name, duration_type), PackageModule
 * rows on (package_id, module_key) - safe to re-run any number of times.
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

        // Legacy USD / superseded catalog rows - keep data but hide from pricing.
        // ("Professional" is deliberately NOT in this list - it's now a live tier name.)
        Package::where('is_deleted', 0)
            ->whereIn('name', ['Basic Plan', 'Growth'])
            ->update(['status' => 0, 'date_updated' => now()]);
    }

    protected function plans(): array
    {
        $out = [$this->freeTrial()];

        foreach (['monthly', 'yearly'] as $duration) {
            $out[] = $this->starter($duration);
            $out[] = $this->business($duration);
            $out[] = $this->professional($duration);
            $out[] = $this->enterprise($duration);
        }

        return $out;
    }

    /**
     * One-time, monthly-only (30 days), never sold - assigned exactly once at
     * business registration. Re-assignment is blocked centrally in
     * SubscriptionService via businesses.trial_used_at, not here.
     */
    protected function freeTrial(): array
    {
        return array_merge($this->priced('Free Trial', 0, 0, 'monthly'), [
            'code' => 'TRIAL-30',
            'trial_days' => 30,
            'setup_fee' => 0,
            'description' => 'Full-system 30-day trial for a newly registered business to test every major module before purchasing.',
            'tagline' => 'Test the complete system, free for 30 days.',
            'badge' => 'Free Trial',
            'best_for' => 'New businesses evaluating the platform',
            'support' => 'Email support',
            'cta' => 'Start Free Trial',
            'modules' => [
                'umbrellas' => ['inventory', 'pos', 'accounting', 'hrm', 'payroll', 'service-management', 'manufacturing', 'analytics'],
                'unlimited' => [],
                'limits' => [
                    'branch' => 1, 'user' => 3, 'warehouse' => 2,
                    'department' => 2, 'designation' => 2, 'shift' => 2, 'employee' => 3,
                    'customer' => 5, 'supplier' => 2, 'product' => 15, 'product-variation' => 15,
                    'order' => 50, 'order-return' => 3,
                    'purchase' => 5, 'purchase-return' => 3, 'purchase-request' => 3, 'purchase-request-quotation' => 3, 'good-receipt-note' => 5,
                    'stock-taking' => 3, 'transfer-note' => 3,
                    'service-purchase' => 3, 'service-purchase-return' => 3, 'service-sale' => 3, 'service-sale-return' => 3,
                    'journal-entry' => 50, 'recurring-transaction' => 3, 'expense' => 3, 'admin-expense' => 3,
                    'supplier-payment' => 5, 'customer-payment' => 5, 'payroll' => 1,
                    'payment-gateway' => 1, 'discount' => 1, 'voucher' => 1, 'payment-method' => 1, 'order-type' => 1,
                    'brand' => 5, 'category' => 5, 'sub-category' => 5, 'account' => 20, 'expense-category' => 5, 'budget' => 3,
                    'fixed-asset' => 5, 'recipe' => 5, 'manufacturing-plan' => 5, 'production' => 5,
                    'push-notification-config' => 1, 'push-notification' => 20,
                ],
            ],
        ]);
    }

    protected function starter(string $duration): array
    {
        return array_merge($this->priced('Starter', 5000, 3000, $duration), [
            'code' => $duration === 'yearly' ? 'STARTER-Y' : 'STARTER',
            'description' => 'For a single shop or small mart running day-to-day sales and stock.',
            'tagline' => 'For a single shop finding its footing.',
            'badge' => null,
            'best_for' => 'Single-branch retail, marts & small shops',
            'support' => 'Email support',
            'cta' => 'Choose Starter',
            'modules' => [
                'umbrellas' => ['inventory', 'pos', 'accounting', 'hrm', 'service-management'],
                'unlimited' => [],
                'limits' => [
                    'branch' => 1, 'user' => 3, 'warehouse' => 2,
                    'department' => 10, 'designation' => 20, 'shift' => 10, 'employee' => 10,
                    'customer' => 2000, 'supplier' => 500, 'product' => 2000, 'product-variation' => 2000,
                    'order' => 2000, 'order-return' => 200,
                    'purchase' => 1000, 'purchase-return' => 100, 'purchase-request' => 200, 'purchase-request-quotation' => 200, 'good-receipt-note' => 1000,
                    'stock-taking' => 100, 'transfer-note' => 100,
                    'service-purchase' => 100, 'service-purchase-return' => 100, 'service-sale' => 200, 'service-sale-return' => 100,
                    'journal-entry' => 2000, 'recurring-transaction' => 50, 'expense' => 500, 'admin-expense' => 500,
                    'supplier-payment' => 1000, 'customer-payment' => 1000, 'payroll' => 12,
                    'payment-gateway' => 2, 'discount' => 20, 'voucher' => 20, 'payment-method' => 5, 'order-type' => 5,
                    'brand' => 50, 'category' => 50, 'sub-category' => 50, 'account' => 100, 'expense-category' => 30, 'budget' => 20,
                    'fixed-asset' => 50, 'recipe' => 20, 'manufacturing-plan' => 20, 'production' => 50,
                    'push-notification-config' => 1, 'push-notification' => 2000,
                ],
            ],
        ]);
    }

    protected function business(string $duration): array
    {
        return array_merge($this->priced('Business', 10000, 5000, $duration), [
            'code' => $duration === 'yearly' ? 'BUSINESS-Y' : 'BUSINESS',
            'description' => 'For growing multi-branch retail and wholesale operations.',
            'tagline' => 'For growing operations scaling fast.',
            'badge' => 'Most Popular',
            'best_for' => 'Growing retail chains, marts & small wholesalers',
            'support' => 'Priority chat & email',
            'cta' => 'Choose Business',
            'modules' => [
                'umbrellas' => ['inventory', 'pos', 'accounting', 'hrm', 'payroll', 'service-management'],
                'unlimited' => [],
                'limits' => [
                    'branch' => 3, 'user' => 10, 'warehouse' => 10,
                    'department' => 25, 'designation' => 50, 'shift' => 25, 'employee' => 50,
                    'customer' => 5000, 'supplier' => 2000, 'product' => 5000, 'product-variation' => 5000,
                    'order' => 5000, 'order-return' => 500,
                    'purchase' => 2500, 'purchase-return' => 300, 'purchase-request' => 600, 'purchase-request-quotation' => 600, 'good-receipt-note' => 2500,
                    'stock-taking' => 300, 'transfer-note' => 300,
                    'service-purchase' => 300, 'service-purchase-return' => 300, 'service-sale' => 500, 'service-sale-return' => 300,
                    'journal-entry' => 5000, 'recurring-transaction' => 150, 'expense' => 1500, 'admin-expense' => 1500,
                    'supplier-payment' => 2500, 'customer-payment' => 2500, 'payroll' => 36,
                    'payment-gateway' => 5, 'discount' => 100, 'voucher' => 100, 'payment-method' => 10, 'order-type' => 10,
                    'brand' => 150, 'category' => 150, 'sub-category' => 150, 'account' => 500, 'expense-category' => 80, 'budget' => 50,
                    'fixed-asset' => 150, 'recipe' => 50, 'manufacturing-plan' => 50, 'production' => 150,
                    'push-notification-config' => 1, 'push-notification' => 5000,
                ],
            ],
        ]);
    }

    protected function professional(string $duration): array
    {
        return array_merge($this->priced('Professional', 20000, 10000, $duration), [
            'code' => $duration === 'yearly' ? 'PROFESSIONAL-Y' : 'PROFESSIONAL',
            'description' => 'For multi-branch groups needing manufacturing and analytics on top of core operations.',
            'tagline' => 'For established groups running at scale.',
            'badge' => null,
            'best_for' => 'Multi-branch chains, distributors & manufacturers',
            'support' => 'Priority phone, chat & email',
            'cta' => 'Choose Professional',
            'modules' => [
                'umbrellas' => ['inventory', 'pos', 'accounting', 'hrm', 'payroll', 'service-management', 'manufacturing', 'analytics'],
                'unlimited' => [],
                'limits' => [
                    'branch' => 10, 'user' => 30, 'warehouse' => 30,
                    'department' => 100, 'designation' => 200, 'shift' => 100, 'employee' => 250,
                    'customer' => 15000, 'supplier' => 5000, 'product' => 25000, 'product-variation' => 25000,
                    'order' => 25000, 'order-return' => 2500,
                    'purchase' => 10000, 'purchase-return' => 1000, 'purchase-request' => 2000, 'purchase-request-quotation' => 2000, 'good-receipt-note' => 10000,
                    'stock-taking' => 1000, 'transfer-note' => 1000,
                    'service-purchase' => 1000, 'service-purchase-return' => 1000, 'service-sale' => 2000, 'service-sale-return' => 1000,
                    'journal-entry' => 25000, 'recurring-transaction' => 500, 'expense' => 5000, 'admin-expense' => 5000,
                    'supplier-payment' => 10000, 'customer-payment' => 10000, 'payroll' => 120,
                    'payment-gateway' => 10, 'discount' => 500, 'voucher' => 500, 'payment-method' => 20, 'order-type' => 20,
                    'brand' => 500, 'category' => 500, 'sub-category' => 500, 'account' => 2000, 'expense-category' => 200, 'budget' => 150,
                    'fixed-asset' => 500, 'recipe' => 150, 'manufacturing-plan' => 150, 'production' => 500,
                    'push-notification-config' => 1, 'push-notification' => 25000,
                ],
            ],
        ]);
    }

    /**
     * Unlimited across every operational/package-controlled limit, no
     * exceptions - only technical infrastructure (storage, SMS/WhatsApp/
     * payment-provider limits) applies, and that's outside this catalog.
     */
    protected function enterprise(string $duration): array
    {
        $allLimitedKeys = array_keys(array_filter(
            SubscriptionModuleRegistry::modules(),
            fn ($m) => $m['type'] === 'limited'
        ));

        return array_merge($this->priced('Enterprise', 65000, 20000, $duration), [
            'code' => $duration === 'yearly' ? 'ENTERPRISE-Y' : 'ENTERPRISE',
            'description' => 'Unlimited scale across every module, on dedicated infrastructure.',
            'tagline' => 'Unlimited scale, starting from Rs. 20,000 setup.',
            'badge' => 'Unlimited',
            'best_for' => 'Large multi-branch groups & enterprise distributors',
            'support' => 'Dedicated account manager',
            'cta' => 'Contact Sales',
            'modules' => [
                'umbrellas' => ['inventory', 'pos', 'accounting', 'hrm', 'payroll', 'service-management', 'manufacturing', 'analytics'],
                'unlimited' => $allLimitedKeys,
                'limits' => [],
            ],
        ]);
    }

    /**
     * @param  float  $monthlyPrice  Base monthly list price (PKR)
     * @param  float  $setupFee  One-time onboarding fee (PKR), same for monthly/yearly rows of the same package
     */
    protected function priced(string $name, float $monthlyPrice, float $setupFee, string $duration): array
    {
        $order = ['Free Trial' => 0, 'Starter' => 1, 'Business' => 2, 'Professional' => 3, 'Enterprise' => 4][$name] ?? 9;
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
            'setup_fee' => $setupFee,
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
        $umbrellaKeys = ['inventory', 'pos', 'accounting', 'hrm', 'payroll', 'service-management', 'manufacturing', 'analytics'];
        $resolved = [];

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
                // The parent may itself be a boolean umbrella toggle (check
                // this tier's umbrella list) or another registry module that
                // was just resolved above it (e.g. payment-transaction's
                // parent is payment-gateway, a per-tier limited module, not
                // an umbrella) - inherit whatever that resolved to.
                $isEnabled = in_array($parent, $umbrellaKeys, true)
                    ? in_array($parent, $umbrellas, true)
                    : ($resolved[$parent] ?? false);
            } else {
                $isEnabled = (bool) ($meta['default_enabled'] ?? false);
            }

            $resolved[$key] = $isEnabled;

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
                    'limit_type' => $meta['limit_type'] ?? null,
                ]
            );
        }
    }
}
