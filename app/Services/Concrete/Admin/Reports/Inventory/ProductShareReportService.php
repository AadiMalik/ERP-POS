<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Enums\RoleNames;
use App\Models\Product;
use App\Models\ProductShare;
use App\Services\Concrete\Api\ProductShareService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Per-product share activity (total + one column per platform) for the
 * "Product Shares" report - see docs/developer/06-reports-infrastructure.md.
 * Products aren't branch-scoped (unlike stock), so this deliberately does
 * NOT reuse AppliesInventoryReportScope/applyRoleScope's branch-column
 * filtering (there is no branch dimension on `products`/`product_shares`
 * to filter by) - business-level scoping only.
 */
class ProductShareReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::INVENTORYMANAGER,
        RoleNames::BRANCHADMIN,
        RoleNames::REPORTINGANALYST,
        RoleNames::MARKITINGMANAGER,
    ];

    protected function resolveBusinessId(array $obj): ?string
    {
        $role = getRoleName();

        if (!empty($this->allow_roles) && !in_array($role, $this->allow_roles)) {
            abort(403, 'Unauthorized access.');
        }

        if ($role === RoleNames::SUPERADMIN) {
            return $obj['business_id'] ?? null;
        }

        return Auth::user()->business_id;
    }

    public function build(array $obj): Collection
    {
        $business_id = $this->resolveBusinessId($obj);
        $product_id = $obj['product_id'] ?? null;
        $category_id = $obj['category_id'] ?? null;
        $brand_id = $obj['brand_id'] ?? null;
        $start_date = $obj['start_date'] ?? null;
        $end_date = $obj['end_date'] ?? null;

        $shareQuery = ProductShare::query();
        if (!empty($business_id)) {
            $shareQuery->where('business_id', $business_id);
        }
        if (!empty($product_id)) {
            $shareQuery->where('product_id', $product_id);
        }
        if (!empty($start_date)) {
            $shareQuery->where('date_created', '>=', businessStartOfDay($start_date));
        }
        if (!empty($end_date)) {
            $shareQuery->where('date_created', '<=', businessEndOfDay($end_date));
        }

        $shares = $shareQuery->get(['product_id', 'platform', 'date_created']);
        if ($shares->isEmpty()) {
            return collect();
        }

        $productsQuery = Product::whereIn('product_id', $shares->pluck('product_id')->unique())
            ->where('is_deleted', 0)
            ->with(['category:category_id,name', 'brand:brand_id,name']);

        if (!empty($category_id)) {
            $productsQuery->where('category_id', $category_id);
        }
        if (!empty($brand_id)) {
            $productsQuery->where('brand_id', $brand_id);
        }

        $products = $productsQuery->get()->keyBy('product_id');

        $rows = collect();
        foreach ($shares->groupBy('product_id') as $prod_id => $product_shares) {
            $product = $products->get($prod_id);
            if (!$product) {
                continue; // excluded by the category/brand filter above
            }

            $by_platform = $product_shares->groupBy('platform')->map->count();

            $row = [
                'product_id' => $prod_id,
                'product_name' => $product->name,
                'category_name' => $product->category->name ?? '-',
                'brand_name' => $product->brand->name ?? '-',
                'total_shares' => $product_shares->count(),
                'last_shared_at' => $product_shares->max('date_created'),
            ];
            foreach (ProductShareService::PLATFORMS as $platform) {
                $row[$platform] = $by_platform->get($platform, 0);
            }

            $rows->push((object) $row);
        }

        return $rows->sortByDesc('total_shares')->values();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);
        $totals = [
            'total_shares' => (int) $rows->sum('total_shares'),
            'total_products' => $rows->count(),
        ];

        $table = DataTables::of($rows)
            ->addColumn('product_name', fn ($row) => e($row->product_name))
            ->addColumn('category_name', fn ($row) => e($row->category_name))
            ->addColumn('brand_name', fn ($row) => e($row->brand_name))
            ->addColumn('total_shares', fn ($row) => $row->total_shares)
            ->addColumn('last_shared_at', fn ($row) => $row->last_shared_at ? localDateTime($row->last_shared_at) : '-');

        foreach (ProductShareService::PLATFORMS as $platform) {
            $table->addColumn($platform, fn ($row) => $row->{$platform});
        }

        return $table->with($totals)->make(true);
    }
}
