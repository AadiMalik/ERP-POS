<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariationStock;
use App\Services\Concrete\Admin\Reports\Inventory\Concerns\AppliesInventoryReportScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Recipe/BOM master: bom | cost_analysis | material_requirement | coverage.
 * Recipes are not versioned (one recipe per finished variation).
 */
class RecipeBomReportService
{
    use AppliesInventoryReportScope;

    public function build(array $obj): Collection
    {
        $filters = $this->baseFilters($obj);
        $mode = $filters['report_mode'] ?? 'bom';

        if ($mode === 'coverage') {
            return $this->buildCoverage($filters);
        }

        return $this->buildRecipeLines($filters, $obj, $mode);
    }

    protected function buildRecipeLines(array $filters, array $obj, string $mode): Collection
    {
        $multiply = max(1, (float) ($obj['quantity'] ?? 1));

        $q = ProductRecipe::query()
            ->join('products', 'products.product_id', '=', 'product_recipes.product_id')
            ->join('product_variations', 'product_variations.product_variation_id', '=', 'product_recipes.product_variation_id')
            ->join('product_recipe_items', 'product_recipe_items.product_recipe_id', '=', 'product_recipes.product_recipe_id')
            ->leftJoin('products as raw_products', 'raw_products.product_id', '=', 'product_recipe_items.raw_material_product_id')
            ->leftJoin('product_variations as raw_variations', 'raw_variations.product_variation_id', '=', 'product_recipe_items.raw_material_product_variation_id')
            ->leftJoin('units', 'units.unit_id', '=', 'product_recipe_items.unit_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'product_recipe_items.warehouse_id')
            ->where('product_recipes.is_deleted', 0)
            ->where('product_recipe_items.is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $q->where('product_recipes.business_id', $filters['business_id']);
        }
        if (!empty($filters['product_id'])) {
            $q->where('product_recipes.product_id', $filters['product_id']);
        }
        if (!empty($filters['product_variation_id'])) {
            $q->where('product_recipes.product_variation_id', $filters['product_variation_id']);
        }
        if (!empty($obj['raw_material_product_id'])) {
            $q->where('product_recipe_items.raw_material_product_id', $obj['raw_material_product_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $q->where('product_recipe_items.warehouse_id', $filters['warehouse_id']);
        }

        // Recipes are business-scoped only (no branch_id on product_recipes).
        $role = getRoleName();
        if ($role !== \App\Enums\RoleNames::SUPERADMIN) {
            if (!in_array($role, $filters['allow_roles'], true)) {
                abort(403, 'Unauthorized access.');
            }
            $q->where('product_recipes.business_id', auth()->user()->business_id);
        }

        $rows = $q->orderBy('products.name')->get([
            'product_recipes.product_recipe_id',
            'product_recipes.business_id',
            'product_recipes.product_id',
            'product_recipes.product_variation_id',
            'product_recipes.date_updated',
            'products.name as finished_product',
            'product_variations.name as finished_variation',
            'raw_products.name as raw_product',
            'raw_variations.name as raw_variation',
            'raw_variations.product_variation_id as raw_variation_id',
            'raw_variations.purchase_price as purchase_price',
            'product_recipe_items.quantity',
            'product_recipe_items.warehouse_id',
            'units.name as unit_name',
            'warehouses.name as warehouse_name',
        ]);

        $stockAvgs = ProductVariationStock::query()
            ->where('is_deleted', 0)
            ->when(!empty($filters['business_id']), fn ($s) => $s->where('business_id', $filters['business_id']))
            ->select('product_variation_id', DB::raw('AVG(avg_price) as avg_price'), DB::raw('SUM(quantity - reserved_quantity) as available_qty'))
            ->groupBy('product_variation_id')
            ->get()
            ->keyBy('product_variation_id');

        return $rows->map(function ($row) use ($multiply, $mode, $stockAvgs) {
            $qty = (float) $row->quantity * $multiply;
            $stock = $stockAvgs->get($row->raw_variation_id);
            $unit_cost = $stock ? (float) $stock->avg_price : (float) ($row->purchase_price ?? 0);
            $available = $stock ? (float) $stock->available_qty : 0;

            return (object) [
                'product_recipe_id' => $row->product_recipe_id,
                'finished_product' => $row->finished_product,
                'finished_variation' => $row->finished_variation,
                'raw_product' => $row->raw_product ?? '-',
                'raw_variation' => $row->raw_variation ?? '-',
                'quantity' => $qty,
                'unit_name' => $row->unit_name ?? '-',
                'warehouse_name' => $row->warehouse_name ?? '-',
                'unit_cost' => $unit_cost,
                'line_cost' => round($qty * $unit_cost, 2),
                'available_qty' => $available,
                'shortfall' => max(0, round($qty - $available, 3)),
                'date_updated' => $row->date_updated,
                'recipe_url' => url('/admin/recipe'),
                'mode' => $mode,
            ];
        });
    }

    protected function buildCoverage(array $filters): Collection
    {
        $q = Product::query()
            ->join('product_variations', 'product_variations.product_id', '=', 'products.product_id')
            ->leftJoin('product_recipes', function ($join) {
                $join->on('product_recipes.product_variation_id', '=', 'product_variations.product_variation_id')
                    ->where('product_recipes.is_deleted', 0);
            })
            ->where('products.is_deleted', 0)
            ->where('product_variations.is_deleted', 0)
            ->where(function ($w) {
                $w->where('products.is_manufactured', 1)
                    ->orWhereNotNull('product_recipes.product_recipe_id');
            });

        if (!empty($filters['business_id'])) {
            $q->where('products.business_id', $filters['business_id']);
        }
        if (!empty($filters['product_id'])) {
            $q->where('products.product_id', $filters['product_id']);
        }

        $role = getRoleName();
        if ($role !== \App\Enums\RoleNames::SUPERADMIN) {
            if (!in_array($role, $filters['allow_roles'], true)) {
                abort(403, 'Unauthorized access.');
            }
            $q->where('products.business_id', auth()->user()->business_id);
        }

        return $q->orderBy('products.name')->get([
            'products.product_id',
            'products.name as finished_product',
            'product_variations.product_variation_id',
            'product_variations.name as finished_variation',
            'product_recipes.product_recipe_id',
            'product_recipes.date_updated',
        ])->map(function ($row) {
            $has = !empty($row->product_recipe_id);

            return (object) [
                'finished_product' => $row->finished_product,
                'finished_variation' => $row->finished_variation,
                'raw_product' => '-',
                'raw_variation' => '-',
                'quantity' => null,
                'unit_name' => '-',
                'warehouse_name' => '-',
                'unit_cost' => null,
                'line_cost' => null,
                'available_qty' => null,
                'shortfall' => null,
                'date_updated' => $row->date_updated,
                'has_recipe' => $has ? 'Yes' : 'No',
                'recipe_url' => url('/admin/recipe'),
                'mode' => 'coverage',
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);
        $mode = $obj['report_mode'] ?? 'bom';
        $totals = [
            'total_line_cost' => currency(round($rows->sum(fn ($r) => (float) ($r->line_cost ?? 0)), 2)),
            'total_shortfall' => decimal(round($rows->sum(fn ($r) => (float) ($r->shortfall ?? 0)), 3)),
        ];

        return DataTables::of($rows)
            ->addColumn('finished_product', function ($row) {
                return '<a href="' . e($row->recipe_url) . '">' . e($row->finished_product) . '</a>';
            })
            ->addColumn('finished_variation', fn ($row) => e($row->finished_variation))
            ->addColumn('raw_product', fn ($row) => e($row->raw_product))
            ->addColumn('raw_variation', fn ($row) => e($row->raw_variation))
            ->addColumn('quantity', fn ($row) => $row->quantity !== null ? decimal($row->quantity) : '-')
            ->addColumn('unit_name', fn ($row) => e($row->unit_name))
            ->addColumn('warehouse_name', fn ($row) => e($row->warehouse_name))
            ->addColumn('unit_cost', fn ($row) => $row->unit_cost !== null ? currency($row->unit_cost) : '-')
            ->addColumn('line_cost', fn ($row) => $row->line_cost !== null ? currency($row->line_cost) : '-')
            ->addColumn('available_qty', fn ($row) => $row->available_qty !== null ? decimal($row->available_qty) : '-')
            ->addColumn('shortfall', fn ($row) => $row->shortfall !== null ? decimal($row->shortfall) : '-')
            ->addColumn('has_recipe', fn ($row) => e($row->has_recipe ?? ($mode === 'coverage' ? 'No' : 'Yes')))
            ->addColumn('date_updated', fn ($row) => $row->date_updated ? localDate($row->date_updated) : '-')
            ->rawColumns(['finished_product'])
            ->with($totals)
            ->make(true);
    }
}
