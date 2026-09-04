<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Models\ProductVariationStock;
use App\Services\Concrete\Admin\Reports\Inventory\Concerns\AppliesInventoryReportScope;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Stock Valuation — on-hand qty × moving average cost (avg_price).
 */
class StockValuationReportService
{
    use AppliesInventoryReportScope;

    public function build(array $obj): Collection
    {
        $filters = $this->baseFilters($obj);

        $q = ProductVariationStock::query()
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'product_variation_stocks.warehouse_id')
            ->leftJoin('branches', 'branches.branch_id', '=', 'warehouses.branch_id')
            ->join('products', 'products.product_id', '=', 'product_variation_stocks.product_id')
            ->join('product_variations', 'product_variations.product_variation_id', '=', 'product_variation_stocks.product_variation_id')
            ->leftJoin('categories', 'categories.category_id', '=', 'products.category_id')
            ->where('product_variation_stocks.is_deleted', 0)
            ->where('product_variation_stocks.quantity', '!=', 0);

        if (!empty($filters['business_id'])) {
            $q->where('product_variation_stocks.business_id', $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $q->where('warehouses.branch_id', $filters['branch_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $q->where('product_variation_stocks.warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['product_id'])) {
            $q->where('product_variation_stocks.product_id', $filters['product_id']);
        }
        if (!empty($filters['product_variation_id'])) {
            $q->where('product_variation_stocks.product_variation_id', $filters['product_variation_id']);
        }
        if (!empty($filters['category_id'])) {
            $q->where('products.category_id', $filters['category_id']);
        }
        if (!empty($filters['brand_id'])) {
            $q->where('products.brand_id', $filters['brand_id']);
        }

        $q = applyRoleScope($q, $filters['allow_roles'], 'product_variation_stocks.business_id', 'warehouses.branch_id');

        return $q->orderBy('products.name')->get([
            'product_variation_stocks.product_id',
            'product_variation_stocks.product_variation_id',
            'product_variation_stocks.warehouse_id',
            'product_variation_stocks.business_id',
            'product_variation_stocks.quantity',
            'product_variation_stocks.avg_price',
            'products.name as product_name',
            'product_variations.name as variation_name',
            'warehouses.name as warehouse_name',
            'branches.name as branch_name',
            'categories.name as category_name',
        ])->map(function ($row) {
            $qty = (float) $row->quantity;
            $avg = (float) $row->avg_price;

            return (object) [
                'product_name' => $row->product_name,
                'variation_name' => $row->variation_name,
                'warehouse_name' => $row->warehouse_name,
                'branch_name' => $row->branch_name,
                'category_name' => $row->category_name,
                'quantity' => $qty,
                'avg_price' => $avg,
                'stock_value' => round($qty * $avg, 2),
                'ledger_url' => url('/admin/reports/stock-ledger') . '?' . http_build_query([
                    'product_id' => $row->product_id,
                    'product_variation_id' => $row->product_variation_id,
                    'warehouse_id' => $row->warehouse_id,
                    'business_id' => $row->business_id,
                ]),
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);
        $totals = [
            'total_qty' => decimal(round($rows->sum('quantity'), 3)),
            'total_value' => currency(round($rows->sum('stock_value'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('product_name', fn ($row) => '<a href="' . e($row->ledger_url) . '">' . e($row->product_name) . '</a>')
            ->addColumn('variation_name', fn ($row) => e($row->variation_name))
            ->addColumn('warehouse_name', fn ($row) => e($row->warehouse_name))
            ->addColumn('branch_name', fn ($row) => e($row->branch_name ?? '-'))
            ->addColumn('category_name', fn ($row) => e($row->category_name ?? '-'))
            ->addColumn('quantity', fn ($row) => decimal($row->quantity))
            ->addColumn('avg_price', fn ($row) => currency($row->avg_price))
            ->addColumn('stock_value', fn ($row) => currency($row->stock_value))
            ->rawColumns(['product_name'])
            ->with($totals)
            ->make(true);
    }
}
