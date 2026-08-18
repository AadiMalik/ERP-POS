<?php

namespace App\Services\Concrete\Admin\Dashboard;

use App\Models\InventorySetting;
use App\Models\Product;
use App\Models\ProductVariationStock;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Stock/inventory KPIs for a dashboard scope. No existing aggregate to
 * reuse anywhere in the codebase, so these are fresh queries over
 * ProductVariationStock. It's keyed by warehouse_id (not branch_id), so a
 * resolved branch is mapped to its warehouse(s) via Warehouse.branch_id
 * before filtering stock.
 */
class DashboardInventoryService
{
    protected const DEFAULT_LOW_STOCK_QUANTITY = 5;

    public function build(array $scope): array
    {
        $warehouse_ids = $this->warehouseIds($scope);

        $stockQuery = fn () => ProductVariationStock::query()
            ->where('product_variation_stocks.business_id', $scope['business_id'])
            ->where('product_variation_stocks.is_deleted', 0)
            ->when($warehouse_ids !== null, fn ($q) => $q->whereIn('product_variation_stocks.warehouse_id', $warehouse_ids));

        $stock_value = (float) $stockQuery()->sum(DB::raw('avg_price * quantity'));

        $low_stock_quantity = $this->lowStockThreshold($scope['business_id']);

        $low_stock_count = $stockQuery()
            ->join('product_variations', 'product_variations.product_variation_id', '=', 'product_variation_stocks.product_variation_id')
            ->where('product_variation_stocks.quantity', '>', 0)
            ->whereRaw('product_variation_stocks.quantity <= COALESCE(NULLIF(product_variations.minimum_stock, 0), ?)', [$low_stock_quantity])
            ->count();

        $out_of_stock_count = $stockQuery()->where('product_variation_stocks.quantity', '<=', 0)->count();

        $total_products = Product::where('business_id', $scope['business_id'])
            ->where('is_deleted', 0)
            ->where('is_track_stock', 1)
            ->count();

        return [
            'stock_value' => $stock_value,
            'low_stock_count' => $low_stock_count,
            'out_of_stock_count' => $out_of_stock_count,
            'total_products' => $total_products,
            // Pure arithmetic on the numbers already computed above - no new query.
            'in_stock_count' => max($total_products - $low_stock_count - $out_of_stock_count, 0),
        ];
    }

    protected function warehouseIds(array $scope): ?array
    {
        if (empty($scope['effective_branch_id'])) {
            return null; // All Branches - no warehouse restriction
        }

        return Warehouse::where('business_id', $scope['business_id'])
            ->where('branch_id', $scope['effective_branch_id'])
            ->where('is_deleted', 0)
            ->pluck('warehouse_id')
            ->all();
    }

    protected function lowStockThreshold($business_id): int
    {
        $setting = InventorySetting::where('business_id', $business_id)->first();

        return (int) ($setting->low_stock_quantity ?? self::DEFAULT_LOW_STOCK_QUANTITY);
    }
}
