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
use App\Models\RecurringTransaction;
use App\Models\Shift;
use App\Models\StockTaking;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\TransferNote;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Warehouse;
use App\Support\Subscription\SubscriptionModuleRegistry;
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
     * @return array{status: bool, message: string}
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

            if ($package->moduleIsUnlimited($moduleKey)) {
                return [
                    'status' => true,
                    'message' => 'Unlimited access',
                ];
            }

            $limit = $package->moduleLimit($moduleKey) ?? 0;
            $count = $this->resolveCount($moduleKey, $business);

            if ($count >= $limit) {
                return [
                    'status' => false,
                    'message' => ucfirst($type) . ' limit exceeded',
                ];
            }

            return [
                'status' => true,
                'message' => ucfirst($type) . ' limit available',
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
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

    protected function resolveCount(string $moduleKey, Business $business): int
    {
        $businessId = $business->business_id;

        return match ($moduleKey) {
            'branch' => Branch::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'user' => User::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'customer' => CustomerProfile::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'warehouse' => Warehouse::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'brand' => Brand::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'category' => Category::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'sub-category' => SubCategory::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'product' => Product::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'product-variation' => ProductVariation::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'stock-taking' => StockTaking::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'transfer-note' => TransferNote::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'supplier' => Supplier::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'purchase-request' => PurchaseRequest::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'purchase-request-quotation' => PurchaseRequestQuotation::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'purchase' => Purchase::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'good-receipt-note' => GoodReceiptNote::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'purchase-return' => PurchaseReturn::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'supplier-payment' => SupplierPayment::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'account' => Account::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'journal-entry' => JournalEntry::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'recurring-transaction' => RecurringTransaction::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'voucher' => Voucher::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'expense' => Expense::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'expense-category' => ExpenseCategory::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'admin-expense' => Expense::where('business_id', $businessId)->where('is_deleted', 0)->where('source', 'admin')->count(),
            'payment-method' => PaymentMethod::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'discount' => Discount::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'order' => Order::where('business_id', $businessId)->where('status', 'posted')->where('is_deleted', 0)->count(),
            'department' => Department::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'designation' => Designation::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'shift' => Shift::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'employee' => Employee::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            'payroll' => PayrollRun::where('business_id', $businessId)->where('is_deleted', 0)->count(),
            default => 0,
        };
    }
}
