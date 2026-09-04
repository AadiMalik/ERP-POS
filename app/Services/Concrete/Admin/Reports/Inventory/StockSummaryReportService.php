<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Models\ProductVariationStock;
use App\Services\Concrete\Admin\Reports\Inventory\Concerns\AppliesInventoryReportScope;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Master Stock Summary covering: Stock Summary, Availability,
 * Warehouse & Branch Stock, and Low Stock & Reorder via report_mode.
 * Source of truth: product_variation_stocks (+ variation minimum_stock).
 */
class StockSummaryReportService
{
    use AppliesInventoryReportScope;

    public function build(array $obj): Collection
    {
        return $this->query($this->baseFilters($obj))->get()->map(function ($row) {
            $qty = (float) $row->quantity;
            $reserved = (float) $row->reserved_quantity;
            $available = $qty - $reserved;
            $avg = (float) $row->avg_price;
            $min = (float) ($row->minimum_stock ?? 0);

            return (object) [
                'product_variation_stock_id' => $row->product_variation_stock_id,
                'business_id' => $row->business_id,
                'product_id' => $row->product_id,
                'product_variation_id' => $row->product_variation_id,
                'warehouse_id' => $row->warehouse_id,
                'product_name' => $row->product_name,
                'variation_name' => $row->variation_name,
                'warehouse_name' => $row->warehouse_name,
                'branch_name' => $row->branch_name,
                'category_name' => $row->category_name,
                'quantity' => $qty,
                'reserved_quantity' => $reserved,
                'available_quantity' => $available,
                'avg_price' => $avg,
                'stock_value' => round($qty * $avg, 2),
                'minimum_stock' => $min,
                'reorder_qty' => $min > 0 && $available < $min ? round($min - $available, 3) : 0,
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
            'total_reserved' => decimal(round($rows->sum('reserved_quantity'), 3)),
            'total_available' => decimal(round($rows->sum('available_quantity'), 3)),
            'total_value' => currency(round($rows->sum('stock_value'), 2)),
            'total_reorder' => decimal(round($rows->sum('reorder_qty'), 3)),
        ];

        return DataTables::of($rows)
            ->addColumn('product_name', function ($row) {
                return '<a href="' . e($row->ledger_url) . '">' . e($row->product_name) . '</a>';
            })
            ->addColumn('variation_name', fn ($row) => e($row->variation_name))
            ->addColumn('warehouse_name', fn ($row) => e($row->warehouse_name))
            ->addColumn('branch_name', fn ($row) => e($row->branch_name))
            ->addColumn('category_name', fn ($row) => e($row->category_name ?? '-'))
            ->addColumn('quantity', fn ($row) => decimal($row->quantity))
            ->addColumn('reserved_quantity', fn ($row) => decimal($row->reserved_quantity))
            ->addColumn('available_quantity', fn ($row) => decimal($row->available_quantity))
            ->addColumn('avg_price', fn ($row) => currency($row->avg_price))
            ->addColumn('stock_value', fn ($row) => currency($row->stock_value))
            ->addColumn('minimum_stock', fn ($row) => decimal($row->minimum_stock))
            ->addColumn('reorder_qty', fn ($row) => $row->reorder_qty > 0 ? decimal($row->reorder_qty) : '-')
            ->rawColumns(['product_name'])
            ->with($totals)
            ->make(true);
    }

    protected function query(array $filters)
    {
        $q = ProductVariationStock::query()
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'product_variation_stocks.warehouse_id')
            ->leftJoin('branches', 'branches.branch_id', '=', 'warehouses.branch_id')
            ->join('products', 'products.product_id', '=', 'product_variation_stocks.product_id')
            ->join('product_variations', 'product_variations.product_variation_id', '=', 'product_variation_stocks.product_variation_id')
            ->leftJoin('categories', 'categories.category_id', '=', 'products.category_id')
            ->where('product_variation_stocks.is_deleted', 0)
            ->where('products.is_deleted', 0)
            ->where('product_variations.is_deleted', 0);

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
        if (!empty($filters['sub_category_id'])) {
            $q->where('products.sub_category_id', $filters['sub_category_id']);
        }
        if (!empty($filters['brand_id'])) {
            $q->where('products.brand_id', $filters['brand_id']);
        }

        $mode = $filters['report_mode'] ?? 'summary';
        if ($mode === 'availability') {
            $q->whereRaw('(product_variation_stocks.quantity - product_variation_stocks.reserved_quantity) > 0');
        } elseif ($mode === 'low_stock') {
            $q->where('product_variations.minimum_stock', '>', 0)
                ->whereRaw('(product_variation_stocks.quantity - product_variation_stocks.reserved_quantity) <= product_variations.minimum_stock');
        }

        $q = applyRoleScope($q, $filters['allow_roles'], 'product_variation_stocks.business_id', 'warehouses.branch_id');

        return $q->orderBy('products.name')
            ->orderBy('product_variations.name')
            ->select([
                'product_variation_stocks.product_variation_stock_id',
                'product_variation_stocks.business_id',
                'product_variation_stocks.product_id',
                'product_variation_stocks.product_variation_id',
                'product_variation_stocks.warehouse_id',
                'product_variation_stocks.quantity',
                'product_variation_stocks.reserved_quantity',
                'product_variation_stocks.avg_price',
                'products.name as product_name',
                'product_variations.name as variation_name',
                'product_variations.minimum_stock',
                'warehouses.name as warehouse_name',
                'branches.name as branch_name',
                'categories.name as category_name',
            ]);
    }
}
