<?php

namespace App\Services\Concrete\Admin;

use App\Enums\RoleNames;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Business;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Discount;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\GoodReceiptNote;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\PayrollRun;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Purchase;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestQuotation;
use App\Models\PurchaseReturn;
use App\Models\Package;
use App\Models\RecurringTransaction;
use App\Models\ServicePurchase;
use App\Models\ServicePurchaseReturn;
use App\Models\ServiceSale;
use App\Models\ServiceSaleReturn;
use App\Models\Shift;
use App\Models\StockTaking;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\TransferNote;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Warehouse;
use App\Models\Budget;
use App\Models\BroadcastNotification;
use App\Models\BroadcastNotificationRecipient;
use App\Models\CustomerPayment;
use App\Models\FirebaseSetting;
use App\Support\Subscription\SubscriptionModuleRegistry;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * Centralizes every subscription module-access/limit check. `hasModule()`
 * answers "is this module available at all" (feature gate), `check()`
 * answers "is there room left under the configured limit" (numeric gate).
 * Both resolve through SubscriptionModuleRegistry + the business's
 * `package_modules` rows (see Package::modules()), not the legacy
 * `is_*_enabled` / `max_*` columns on `packages` (still present, but only
 * read by the one-time backfill migration now).
 */
class FeatureLimitService
{
    /**
     * Legacy checkPackageLimit() type-name aliases, kept so none of the
     * pre-existing call sites (BranchService, CategoryService,
     * SubCategoryService, ProductService, SupplierService, WarehouseService,
     * OrderService) need to change.
     */
    private const LEGACY_ALIASES = [
        'branches' => 'branch',
        'users' => 'user',
        'customers' => 'customer',
        'warehouses' => 'warehouse',
        'categories' => 'category',
        'products' => 'product',
        'suppliers' => 'supplier',
        'purchase_orders' => 'purchase-request',
        'purchases' => 'purchase',
        'sales' => 'order',
        'transfers' => 'transfer-note',
        'expenses' => 'expense',
        'vouchers' => 'voucher',
    ];

    /**
     * @return array{status: bool, message: string, resource?: string, used?: int, limit?: int, period?: string, upgrade_required?: bool}
     */
    public function check(string $type, ?Business $business = null): array
    {
        try {
            if (getRoleName() == RoleNames::SUPERADMIN) {
                return [
                    'status' => true,
                    'message' => 'Super admin bypass',
                ];
            }

            $moduleKey = self::LEGACY_ALIASES[$type] ?? $type;

            $business = $business ?? Auth::user()->business;

            if (!$business) {
                return [
                    'status' => false,
                    'message' => 'Business not found',
                ];
            }

            $business->loadMissing('package.modules');

            if (!$business->package) {
                return [
                    'status' => false,
                    'message' => 'Package not found',
                ];
            }

            if (!SubscriptionModuleRegistry::isLimited($moduleKey)) {
                return [
                    'status' => false,
                    'message' => 'Invalid limit type',
                ];
            }

            $package = $business->package;
            $meta = SubscriptionModuleRegistry::find($moduleKey);
            $label = $meta['label'] ?? ucfirst($type);
            $isMonthly = in_array($meta['limit_type'] ?? null, ['monthly_creation', 'monthly_transaction'], true);
            $period = $isMonthly ? $this->currentUsagePeriod($business) : null;
            $periodLabel = $period ? ('billing period ' . $period['start']->toDateString() . ' to ' . $period['end']->copy()->subDay()->toDateString()) : 'current';

            if ($package->moduleIsUnlimited($moduleKey)) {
                return [
                    'status' => true,
                    'message' => 'Unlimited access',
                ];
            }

            $limit = $package->moduleLimit($moduleKey) ?? 0;
            $count = $this->resolveCount($moduleKey, $business);

            if ($count >= $limit) {
                $qualifier = $isMonthly ? 'monthly ' : '';

                return [
                    'status' => false,
                    'message' => "You have reached your {$qualifier}{$label} limit of {$limit} for the {$package->name} package. Please upgrade your package to continue.",
                    'resource' => $label,
                    'used' => $count,
                    'limit' => $limit,
                    'period' => $periodLabel,
                    'upgrade_required' => true,
                ];
            }

            return [
                'status' => true,
                'message' => ucfirst($type) . ' limit available',
                'resource' => $label,
                'used' => $count,
                'limit' => $limit,
                'period' => $periodLabel,
                'upgrade_required' => false,
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Current monthly usage window for $business, anchored on its active
     * subscription's start_at and rolling forward in whole-month increments -
     * so a yearly subscription still resets its monthly counters every month
     * on its own anniversary day, rather than granting 12 months of usage at
     * once or resetting on the calendar month.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    public function currentUsagePeriod(Business $business): array
    {
        $anchor = $business->currentSubscription?->start_at
            ?? $business->subscription_start
            ?? $business->date_created
            ?? now();
        $anchor = Carbon::parse($anchor);

        $months = $anchor->diffInMonths(now());
        $start = $anchor->copy()->addMonths($months);

        // diffInMonths can land start slightly after now() due to partial
        // month rounding - step back one month if so.
        if ($start->gt(now())) {
            $months--;
            $start = $anchor->copy()->addMonths($months);
        }

        return [
            'start' => $start,
            'end' => $start->copy()->addMonth(),
        ];
    }

    public function checkAndAbort(string $type, ?Business $business = null): void
    {
        $result = $this->check($type, $business);

        if (!$result['status']) {
            abort(403, $result['message']);
        }
    }

    /**
     * Whether $module (a SubscriptionModuleRegistry key) is available to the
     * business at all - independent of any numeric limit. `core` modules are
     * always available; everything else needs its own package_modules
     * `is_enabled` flag, and (if it declares a `parent` umbrella) the
     * parent's flag too.
     */
    public function hasModule(string $module, ?Business $business = null): bool
    {
        if (getRoleName() == RoleNames::SUPERADMIN) {
            return true;
        }

        $meta = SubscriptionModuleRegistry::find($module);

        if ($meta && $meta['type'] === 'core') {
            return true;
        }

        $business = $business ?? Auth::user()->business;

        if (!$business) {
            return false;
        }

        $business->loadMissing('package.modules');

        if (!$business->package) {
            return false;
        }

        if (!$business->package->moduleEnabled($module)) {
            return false;
        }

        $parent = $meta['parent'] ?? null;

        if ($parent && !$business->package->moduleEnabled($parent)) {
            return false;
        }

        return true;
    }

    /**
     * @return array{used:int, limit:?int, unlimited:bool, remaining:?int}
     */
    public function usage(string $moduleKey, ?Business $business = null): array
    {
        $business = $business ?? Auth::user()->business;
        $business?->loadMissing('package.modules');
        $package = $business?->package;

        $unlimited = $package ? $package->moduleIsUnlimited($moduleKey) : false;
        $limit = $package ? $package->moduleLimit($moduleKey) : null;
        $used = ($business && SubscriptionModuleRegistry::isLimited($moduleKey))
            ? $this->resolveCount($moduleKey, $business)
            : 0;

        return [
            'used' => $used,
            'limit' => $unlimited ? null : $limit,
            'unlimited' => $unlimited,
            'remaining' => ($unlimited || $limit === null) ? null : max(0, $limit - $used),
        ];
    }

    /**
     * Compare current business usage against a target package's enable/limit
     * matrix. Used to block a downgrade (or any plan change) when the
     * tenant already has more records than the target plan allows.
     *
     * @param array<string,int>|null $usageByKey Precomputed counts from usageByLimitedKey()
     * @return list<array{key:string,label:string,used:int,allowed:int,excess:int}>
     */
    public function compareToPackage(Business $business, Package $target, ?array $usageByKey = null): array
    {
        $target->loadMissing('modules');
        $usageByKey = $usageByKey ?? $this->usageByLimitedKey($business);
        $blockers = [];

        foreach (SubscriptionModuleRegistry::modules() as $key => $meta) {
            if ($meta['type'] !== 'limited') {
                continue;
            }

            $used = $usageByKey[$key] ?? 0;
            $parent = $meta['parent'] ?? null;
            $enabled = $target->moduleEnabled($key);

            if ($parent && !$target->moduleEnabled($parent)) {
                $enabled = false;
            }

            if (!$enabled) {
                if ($used > 0) {
                    $blockers[] = [
                        'key' => $key,
                        'label' => $meta['label'],
                        'used' => $used,
                        'allowed' => 0,
                        'excess' => $used,
                    ];
                }

                continue;
            }

            if ($target->moduleIsUnlimited($key)) {
                continue;
            }

            $limit = $target->moduleLimit($key) ?? 0;

            if ($used > $limit) {
                $blockers[] = [
                    'key' => $key,
                    'label' => $meta['label'],
                    'used' => $used,
                    'allowed' => $limit,
                    'excess' => $used - $limit,
                ];
            }
        }

        return $blockers;
    }

    /**
     * @param list<array{key:string,label:string,used:int,allowed:int,excess:int}> $blockers
     */
    public function formatCompareBlockersMessage(Package $target, array $blockers): string
    {
        $lines = [
            'You cannot switch to ' . $target->name . ' yet. Reduce these first, then you can change plans:',
        ];

        foreach ($blockers as $blocker) {
            if ((int) $blocker['allowed'] === 0) {
                $lines[] = $blocker['label'] . ': ' . $blocker['used'] . ' used, not included on this plan (remove ' . $blocker['excess'] . ')';
            } else {
                $lines[] = $blocker['label'] . ': ' . $blocker['used'] . ' used, plan allows ' . $blocker['allowed'] . ' (remove ' . $blocker['excess'] . ')';
            }
        }

        return implode(' ', $lines);
    }

    /**
     * @throws Exception
     */
    public function assertCompatibleWithPackage(Business $business, Package $target): void
    {
        $blockers = $this->compareToPackage($business, $target);

        if ($blockers) {
            throw new Exception($this->formatCompareBlockersMessage($target, $blockers));
        }
    }

    /**
     * @return array<string,int>
     */
    public function usageByLimitedKey(Business $business): array
    {
        $counts = [];

        foreach (SubscriptionModuleRegistry::modules() as $key => $meta) {
            if ($meta['type'] === 'limited') {
                $counts[$key] = $this->resolveCount($key, $business);
            }
        }

        return $counts;
    }

    /**
     * active_count/configuration_count modules: current live rows
     * (soft-deleted excluded) - unaffected by billing period.
     * monthly_creation/monthly_transaction modules: every row created within
     * the business's current billing period, soft-deleted/cancelled/void
     * rows included - never excluded by status, so status changes cannot be
     * used to free up quota. Deletion never reduces this count either way,
     * since it only ever counts creation dates within the window, not
     * current live state.
     */
    protected function resolveCount(string $moduleKey, Business $business): int
    {
        $businessId = $business->business_id;
        $meta = SubscriptionModuleRegistry::find($moduleKey);
        $isMonthly = in_array($meta['limit_type'] ?? null, ['monthly_creation', 'monthly_transaction'], true);

        $query = match ($moduleKey) {
            'branch' => Branch::where('business_id', $businessId),
            'user' => User::where('business_id', $businessId),
            'customer' => CustomerProfile::where('business_id', $businessId),
            'warehouse' => Warehouse::where('business_id', $businessId),
            'brand' => Brand::where('business_id', $businessId),
            'category' => Category::where('business_id', $businessId),
            'sub-category' => SubCategory::where('business_id', $businessId),
            'product' => Product::where('business_id', $businessId),
            'product-variation' => ProductVariation::where('business_id', $businessId),
            'stock-taking' => StockTaking::where('business_id', $businessId),
            'transfer-note' => TransferNote::where('business_id', $businessId),
            'supplier' => Supplier::where('business_id', $businessId),
            'purchase-request' => PurchaseRequest::where('business_id', $businessId),
            'purchase-request-quotation' => PurchaseRequestQuotation::where('business_id', $businessId),
            'purchase' => Purchase::where('business_id', $businessId),
            'good-receipt-note' => GoodReceiptNote::where('business_id', $businessId),
            'purchase-return' => PurchaseReturn::where('business_id', $businessId),
            'supplier-payment' => SupplierPayment::where('business_id', $businessId),
            'service-purchase' => ServicePurchase::where('business_id', $businessId),
            'service-purchase-return' => ServicePurchaseReturn::where('business_id', $businessId),
            'service-sale' => ServiceSale::where('business_id', $businessId),
            'service-sale-return' => ServiceSaleReturn::where('business_id', $businessId),
            'account' => Account::where('business_id', $businessId),
            'journal-entry' => JournalEntry::where('business_id', $businessId),
            'recurring-transaction' => RecurringTransaction::where('business_id', $businessId),
            'voucher' => Voucher::where('business_id', $businessId),
            'expense' => Expense::where('business_id', $businessId),
            'expense-category' => ExpenseCategory::where('business_id', $businessId),
            'admin-expense' => Expense::where('business_id', $businessId)->where('source', 'admin'),
            'payment-method' => PaymentMethod::where('business_id', $businessId),
            'discount' => Discount::where('business_id', $businessId),
            'order' => Order::where('business_id', $businessId),
            'order-return' => Order::where('business_id', $businessId)->where('status', 'returned'),
            'customer-payment' => CustomerPayment::where('business_id', $businessId),
            'department' => Department::where('business_id', $businessId),
            'designation' => Designation::where('business_id', $businessId),
            'shift' => Shift::where('business_id', $businessId),
            'employee' => Employee::where('business_id', $businessId),
            'payroll' => PayrollRun::where('business_id', $businessId),
            'budget' => Budget::where('business_id', $businessId),
            'push-notification-config' => FirebaseSetting::where('business_id', $businessId),
            'push-notification' => BroadcastNotificationRecipient::whereIn(
                'broadcast_notification_id',
                BroadcastNotification::where('business_id', $businessId)->pluck('broadcast_notification_id')
            ),
            default => null,
        };

        if (!$query) {
            return 0;
        }

        if ($isMonthly) {
            $period = $this->currentUsagePeriod($business);

            return $query->whereBetween('date_created', [$period['start'], $period['end']])->count();
        }

        // active_count / configuration_count: current live rows only.
        // FirebaseSetting has no is_deleted column (one row per business).
        if ($moduleKey !== 'push-notification-config') {
            $query->where('is_deleted', 0);
        }

        return $query->count();
    }
}
