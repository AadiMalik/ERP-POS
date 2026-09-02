<?php

namespace App\Services\Concrete\Api\Offline;

use App\Enums\Status;
use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\Discount;
use App\Models\ExpenseCategory;
use App\Models\InventorySetting;
use App\Models\OrderSource;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use App\Models\PosDevice;
use App\Models\PosRegister;
use App\Models\PosSetting;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationPrice;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationUnitConversion;
use App\Models\SaleType;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Warehouse;
use App\Services\Concrete\Admin\ThermalPrintSettingResolverService;
use Illuminate\Support\Facades\Schema;

/**
 * Packages master/reference data for desktop POS initial and incremental sync.
 * Uses date_updated cursors per entity type stored on pos_devices.sync_cursors.
 */
class OfflineSyncService
{
    protected $thermal_resolver;

    public function __construct(ThermalPrintSettingResolverService $thermal_resolver)
    {
        $this->thermal_resolver = $thermal_resolver;
    }

    public function bootstrap(PosDevice $device, ?string $warehouse_id = null): array
    {
        $business_id = $device->business_id;
        $branch_id = $device->branch_id;
        $warehouse_id = $warehouse_id ?: optional($device->register)->warehouse_id;

        $payload = [
            'server_time' => now()->toIso8601String(),
            'business_id' => $business_id,
            'branch_id' => $branch_id,
            'warehouse_id' => $warehouse_id,
            'cursors' => $this->buildCursors($business_id),
            'settings' => $this->exportSettings($business_id, $branch_id),
            'branches' => $this->exportBranches($business_id),
            'warehouses' => $this->exportWarehouses($business_id),
            'registers' => $this->exportRegisters($business_id, $branch_id),
            'users' => $this->exportPosUsers($business_id),
            'order_types' => $this->exportOrderTypes($business_id),
            'order_sources' => $this->exportOrderSources($business_id),
            'payment_methods' => $this->exportPaymentMethods($business_id),
            'sale_types' => $this->exportSaleTypes($business_id),
            'discounts' => $this->exportDiscounts($business_id),
            'categories' => $this->exportCategories($business_id),
            'expense_categories' => $this->exportExpenseCategories($business_id),
            'products' => $this->exportProducts($business_id),
            'variations' => $this->exportVariations($business_id),
            'variation_prices' => $this->exportVariationPrices($business_id),
            'unit_conversions' => $this->exportUnitConversions($business_id),
            'customers' => $this->exportCustomers($business_id),
            'stock_levels' => $warehouse_id ? $this->exportStockLevels($business_id, $warehouse_id) : [],
            'vouchers' => $this->exportVouchers($business_id),
        ];

        return $payload;
    }

    public function pull(PosDevice $device, array $cursors, ?string $warehouse_id = null): array
    {
        $business_id = $device->business_id;
        $warehouse_id = $warehouse_id ?: optional($device->register)->warehouse_id;
        $since = $cursors ?: ($device->sync_cursors ?? []);

        $changes = [
            'server_time' => now()->toIso8601String(),
            'cursors' => $this->buildCursors($business_id),
            'settings' => $this->exportSettingsChanged($business_id, $device->branch_id, $since['settings'] ?? null),
            'products' => $this->exportProductsChanged($business_id, $since['products'] ?? null),
            'variations' => $this->exportVariationsChanged($business_id, $since['variations'] ?? null),
            'variation_prices' => $this->exportVariationPricesChanged($business_id, $since['variation_prices'] ?? null),
            'customers' => $this->exportCustomersChanged($business_id, $since['customers'] ?? null),
            'stock_levels' => $warehouse_id ? $this->exportStockChanged($business_id, $warehouse_id, $since['stock_levels'] ?? null) : [],
            'users' => $this->exportUsersChanged($business_id, $since['users'] ?? null),
        ];

        return $changes;
    }

    protected function buildCursors(string $business_id): array
    {
        return [
            'settings' => now()->toIso8601String(),
            'products' => $this->maxUpdated(Product::class, 'business_id', $business_id),
            'variations' => $this->maxUpdated(ProductVariation::class, 'business_id', $business_id),
            'variation_prices' => $this->maxUpdated(ProductVariationPrice::class, 'business_id', $business_id),
            'customers' => $this->maxUpdated(CustomerProfile::class, 'business_id', $business_id),
            'stock_levels' => now()->toIso8601String(),
            'users' => $this->maxUpdated(User::class, 'business_id', $business_id),
        ];
    }

    protected function maxUpdated(string $model, string $scope_col, string $scope_val): ?string
    {
        $query = $model::where($scope_col, $scope_val);
        $this->applySoftDeleteScope($query, $model);
        $max = $query->max('date_updated');

        return $max ? (string) $max : null;
    }

    protected function applySoftDeleteScope($query, string $model_class)
    {
        $table = (new $model_class)->getTable();
        if (Schema::hasColumn($table, 'is_deleted')) {
            $query->where('is_deleted', 0);
        }

        return $query;
    }

    protected function exportSettings(string $business_id, ?string $branch_id): array
    {
        $pos = PosSetting::firstOrCreate(['business_id' => $business_id]);
        $business = BusinessSetting::firstOrCreate(['business_id' => $business_id]);
        $inventory = InventorySetting::firstOrCreate(['business_id' => $business_id]);
        $thermal = $this->thermal_resolver->resolve($business_id, $branch_id);

        return [
            'pos_setting' => $pos->toArray(),
            'business_setting' => $business->toArray(),
            'inventory_setting' => $inventory->toArray(),
            // ThermalPrintConfig has no public properties/toArray() of its own
            // (by design - it's a resolved value object, not a model) so it
            // must be flattened here or it JSON-encodes as {}.
            'thermal_print_setting' => [
                'is_enabled' => $thermal->isEnabled(),
                'paper_width_mm' => $thermal->paperWidthMm(),
                'field_config' => $thermal->fieldConfig(),
                'footer_config' => $thermal->footerConfig(),
            ],
        ];
    }

    protected function exportSettingsChanged(string $business_id, ?string $branch_id, ?string $since): array
    {
        if (empty($since)) {
            return $this->exportSettings($business_id, $branch_id);
        }

        return $this->exportSettings($business_id, $branch_id);
    }

    protected function exportBranches(string $business_id)
    {
        return Branch::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->get()
            ->toArray();
    }

    /**
     * Business-scoped, not branch-scoped - warehouses are usually shared
     * across a business's branches (branch_id is nullable on the model) and
     * every other warehouse picker in the app (WarehouseService::getByBusiness(),
     * used by PosScreenController's own branch/warehouse switcher) reflects
     * that. Filtering by branch_id here excluded every warehouse with a null
     * branch_id - i.e. the common case - leaving the desktop's warehouse
     * dropdown empty even though registers/orders already reference one.
     */
    protected function exportWarehouses(string $business_id)
    {
        return Warehouse::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->get()
            ->toArray();
    }

    protected function exportRegisters(string $business_id, ?string $branch_id)
    {
        $q = PosRegister::where('business_id', $business_id)->where('is_deleted', 0)->where('status', Status::ACTIVE);
        if ($branch_id) {
            $q->where('branch_id', $branch_id);
        }

        return $q->get()->toArray();
    }

    public function exportPosUsers(string $business_id): array
    {
        return User::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->where(function ($q) {
                $q->whereHas('permissions', function ($pq) {
                    $pq->where('name', 'pos.access');
                })->orWhereHas('roles.permissions', function ($pq) {
                    $pq->where('name', 'pos.access');
                });
            })
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'business_id' => $user->business_id,
                    'branch_id' => $user->branch_id,
                    'password_hash' => $user->password,
                    'status' => $user->status,
                    'date_updated' => $user->date_updated,
                ];
            })
            ->values()
            ->all();
    }

    protected function exportUsersChanged(string $business_id, ?string $since): array
    {
        $q = User::where('business_id', $business_id)->where('is_deleted', 0);
        if ($since) {
            $q->where('date_updated', '>', $since);
        }

        return $q->get()->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'business_id' => $user->business_id,
                'branch_id' => $user->branch_id,
                'password_hash' => $user->password,
                'status' => $user->status,
                'date_updated' => $user->date_updated,
            ];
        })->values()->all();
    }

    protected function exportOrderTypes(string $business_id)
    {
        return OrderType::where('business_id', $business_id)->where('is_deleted', 0)->where('status', Status::ACTIVE)->get()->toArray();
    }

    protected function exportOrderSources(string $business_id)
    {
        return OrderSource::where('business_id', $business_id)->where('is_deleted', 0)->where('status', Status::ACTIVE)->get()->toArray();
    }

    protected function exportPaymentMethods(string $business_id)
    {
        return PaymentMethod::where('business_id', $business_id)->where('is_deleted', 0)->where('status', Status::ACTIVE)->get()->toArray();
    }

    protected function exportSaleTypes(string $business_id)
    {
        return SaleType::where('business_id', $business_id)->where('is_deleted', 0)->where('status', Status::ACTIVE)->get()->toArray();
    }

    protected function exportDiscounts(string $business_id)
    {
        return Discount::where('business_id', $business_id)->where('is_deleted', 0)->where('status', Status::ACTIVE)->get()->toArray();
    }

    protected function exportCategories(string $business_id)
    {
        return Category::where('business_id', $business_id)->where('is_deleted', 0)->where('status', Status::ACTIVE)->get()->toArray();
    }

    protected function exportExpenseCategories(string $business_id)
    {
        return ExpenseCategory::where('business_id', $business_id)->where('is_deleted', 0)->where('status', Status::ACTIVE)->get()->toArray();
    }

    protected function exportProducts(string $business_id)
    {
        return Product::where('business_id', $business_id)->where('is_deleted', 0)->where('is_pos_visible', 1)->get()->toArray();
    }

    protected function exportProductsChanged(string $business_id, ?string $since)
    {
        $q = Product::where('business_id', $business_id)->where('is_deleted', 0);
        if ($since) {
            $q->where('date_updated', '>', $since);
        }

        return $q->get()->toArray();
    }

    protected function exportVariations(string $business_id)
    {
        return ProductVariation::where('business_id', $business_id)->where('is_deleted', 0)->where('status', Status::ACTIVE)->get()->toArray();
    }

    protected function exportVariationsChanged(string $business_id, ?string $since)
    {
        $q = ProductVariation::where('business_id', $business_id)->where('is_deleted', 0);
        if ($since) {
            $q->where('date_updated', '>', $since);
        }

        return $q->get()->toArray();
    }

    protected function exportVariationPrices(string $business_id)
    {
        return ProductVariationPrice::where('business_id', $business_id)->get()->toArray();
    }

    protected function exportVariationPricesChanged(string $business_id, ?string $since)
    {
        $q = ProductVariationPrice::where('business_id', $business_id);
        if ($since) {
            $q->where('date_updated', '>', $since);
        }

        return $q->get()->toArray();
    }

    protected function exportUnitConversions(string $business_id)
    {
        return ProductVariationUnitConversion::where('business_id', $business_id)->where('is_deleted', 0)->get()->toArray();
    }

    protected function exportCustomers(string $business_id): array
    {
        return CustomerProfile::with('user')
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get()
            ->map(function ($profile) {
                return [
                    'user_id' => $profile->user_id,
                    'code' => $profile->code,
                    'credit_limit' => $profile->credit_limit,
                    'credit_days' => $profile->credit_days,
                    'store_credit_balance' => $profile->store_credit_balance,
                    'is_walkin' => $profile->is_walkin,
                    'name' => $profile->user->name ?? '',
                    'email' => $profile->user->email ?? '',
                    'phone' => $profile->user->phone ?? '',
                    'date_updated' => $profile->date_updated,
                ];
            })
            ->values()
            ->all();
    }

    protected function exportCustomersChanged(string $business_id, ?string $since): array
    {
        $q = CustomerProfile::with('user')->where('business_id', $business_id)->where('is_deleted', 0);
        if ($since) {
            $q->where('date_updated', '>', $since);
        }

        return $q->get()->map(function ($profile) {
            return [
                'user_id' => $profile->user_id,
                'code' => $profile->code,
                'credit_limit' => $profile->credit_limit,
                'credit_days' => $profile->credit_days,
                'store_credit_balance' => $profile->store_credit_balance,
                'is_walkin' => $profile->is_walkin,
                'name' => $profile->user->name ?? '',
                'email' => $profile->user->email ?? '',
                'phone' => $profile->user->phone ?? '',
                'date_updated' => $profile->date_updated,
            ];
        })->values()->all();
    }

    protected function exportStockLevels(string $business_id, string $warehouse_id): array
    {
        return ProductVariationStock::where('business_id', $business_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('is_deleted', 0)
            ->get()
            ->map(function ($row) {
                return [
                    'product_variation_stock_id' => $row->product_variation_stock_id,
                    'product_variation_id' => $row->product_variation_id,
                    'warehouse_id' => $row->warehouse_id,
                    'quantity' => (float) $row->quantity,
                    'date_updated' => $row->date_updated,
                ];
            })
            ->values()
            ->all();
    }

    protected function exportStockChanged(string $business_id, string $warehouse_id, ?string $since): array
    {
        $q = ProductVariationStock::where('business_id', $business_id)->where('warehouse_id', $warehouse_id)->where('is_deleted', 0);
        if ($since) {
            $q->where('date_updated', '>', $since);
        }

        return $q->get()->map(function ($row) {
            return [
                'product_variation_stock_id' => $row->product_variation_stock_id,
                'product_variation_id' => $row->product_variation_id,
                'warehouse_id' => $row->warehouse_id,
                'quantity' => (float) $row->quantity,
                'date_updated' => $row->date_updated,
            ];
        })->values()->all();
    }

    protected function exportVouchers(string $business_id)
    {
        return Voucher::where('business_id', $business_id)->where('is_deleted', 0)->where('status', Status::ACTIVE)->get()->toArray();
    }
}
