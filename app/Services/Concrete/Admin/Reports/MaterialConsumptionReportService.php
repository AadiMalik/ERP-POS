<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Models\ManufacturingPlanMaterial;
use App\Models\ProductionConsumption;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Material Consumption master report.
 * Covers material/product/category/warehouse/production/recipe/plan-wise views,
 * expected vs actual variance, and cost analysis via group_by + report_mode.
 * "Order-wise" maps to manufacturing plan (production order).
 */
class MaterialConsumptionReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::INVENTORYMANAGER,
        RoleNames::BRANCHADMIN,
        RoleNames::REPORTINGANALYST,
    ];

    protected function detailQuery(array $filters)
    {
        $allow_roles = $this->allow_roles;

        $q = ProductionConsumption::with([
            'product.category',
            'productVariation',
            'batch',
            'warehouse',
            'production.plan.productVariation',
            'production.recipe',
            'production.warehouse',
        ])->whereHas('production', function ($p) use ($filters, $allow_roles) {
            $p->where('is_deleted', 0);
            if (!empty($filters['business_id'])) {
                $p->where('business_id', $filters['business_id']);
            }
            if (!empty($filters['branch_id'])) {
                $p->where('branch_id', $filters['branch_id']);
            }
            if (!empty($filters['manufacturing_plan_id'])) {
                $p->where('manufacturing_plan_id', $filters['manufacturing_plan_id']);
            }
            if (!empty($filters['product_recipe_id'])) {
                $p->where('product_recipe_id', $filters['product_recipe_id']);
            }
            applyRoleScope($p, $allow_roles);
        });

        if (!empty($filters['production_id'])) {
            $q->where('production_id', $filters['production_id']);
        }
        if (!empty($filters['product_id'])) {
            $q->where('product_id', $filters['product_id']);
        }
        if (!empty($filters['product_variation_id'])) {
            $q->where('product_variation_id', $filters['product_variation_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $q->where('warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['category_id'])) {
            $q->whereHas('product', fn ($p) => $p->where('category_id', $filters['category_id']));
        }
        if (!empty($filters['start_date'])) {
            $q->where('date_created', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $q->where('date_created', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        return $q;
    }

    public function build(array $filters): Collection
    {
        $mode = $filters['report_mode'] ?? 'detail';
        $group_by = $filters['group_by'] ?? 'detail';

        if (in_array($mode, ['variance', 'expected_vs_actual'], true)) {
            return $this->buildVariance($filters);
        }

        if ($group_by !== 'detail' && $group_by !== '') {
            return $this->buildGrouped($filters, $group_by);
        }

        return $this->detailQuery($filters)->orderByDesc('date_created')->get()->map(function ($item) {
            return (object) [
                'date_created' => $item->date_created,
                'group_label' => $item->productVariation?->name ?? '-',
                'raw_material_name' => $item->productVariation?->name ?? '-',
                'finished_product' => $item->production?->plan?->productVariation?->name ?? '-',
                'batch_no' => $item->batch?->batch_no ?? '-',
                'base_quantity' => (float) $item->base_quantity,
                'expected_qty' => null,
                'variance_qty' => null,
                'variance_pct' => null,
                'efficiency_pct' => null,
                'unit_cost' => (float) $item->unit_cost,
                'total_cost' => (float) $item->total_cost,
                'warehouse_name' => $item->warehouse?->name ?? '-',
                'production_no' => $item->production?->production_no ?? '-',
                'plan_no' => $item->production?->plan?->plan_no ?? '-',
                'recipe_id' => $item->production?->product_recipe_id,
                'production_url' => $item->production_id
                    ? url('/admin/production/edit/' . $item->production_id)
                    : null,
                'plan_url' => $item->production?->manufacturing_plan_id
                    ? url('/admin/manufacturing-plan/edit/' . $item->production->manufacturing_plan_id)
                    : null,
                'line_count' => 1,
            ];
        });
    }

    protected function buildGrouped(array $filters, string $group_by): Collection
    {
        $rows = $this->detailQuery($filters)->get();

        $grouped = $rows->groupBy(function ($item) use ($group_by) {
            return match ($group_by) {
                'material' => $item->product_variation_id,
                'product' => $item->production?->plan?->product_variation_id ?? 'unknown',
                'category' => $item->product?->category_id ?? 'unknown',
                'warehouse' => $item->warehouse_id ?? 'unknown',
                'production' => $item->production_id,
                'recipe' => $item->production?->product_recipe_id ?? 'unknown',
                'plan' => $item->production?->manufacturing_plan_id ?? 'unknown',
                default => $item->production_consumption_id,
            };
        });

        return $grouped->map(function ($items, $key) use ($group_by) {
            $first = $items->first();
            $qty = (float) $items->sum('base_quantity');
            $cost = (float) $items->sum('total_cost');

            $label = match ($group_by) {
                'material' => $first->productVariation?->name ?? $key,
                'product' => $first->production?->plan?->productVariation?->name ?? $key,
                'category' => $first->product?->category?->name ?? $key,
                'warehouse' => $first->warehouse?->name ?? $key,
                'production' => $first->production?->production_no ?? $key,
                'recipe' => $first->production?->product_recipe_id ?? $key,
                'plan' => $first->production?->plan?->plan_no ?? $key,
                default => (string) $key,
            };

            return (object) [
                'date_created' => $items->max('date_created'),
                'group_label' => $label,
                'raw_material_name' => $group_by === 'material' ? $label : ($first->productVariation?->name ?? '-'),
                'finished_product' => $first->production?->plan?->productVariation?->name ?? '-',
                'batch_no' => '-',
                'base_quantity' => $qty,
                'expected_qty' => null,
                'variance_qty' => null,
                'variance_pct' => null,
                'efficiency_pct' => null,
                'unit_cost' => $qty > 0 ? round($cost / $qty, 4) : 0,
                'total_cost' => $cost,
                'warehouse_name' => $group_by === 'warehouse' ? $label : ($first->warehouse?->name ?? '-'),
                'production_no' => $group_by === 'production' ? $label : ($first->production?->production_no ?? '-'),
                'plan_no' => $group_by === 'plan' ? $label : ($first->production?->plan?->plan_no ?? '-'),
                'recipe_id' => $first->production?->product_recipe_id,
                'production_url' => $first->production_id
                    ? url('/admin/production/edit/' . $first->production_id)
                    : null,
                'plan_url' => $first->production?->manufacturing_plan_id
                    ? url('/admin/manufacturing-plan/edit/' . $first->production->manufacturing_plan_id)
                    : null,
                'line_count' => $items->count(),
            ];
        })->values();
    }

    protected function buildVariance(array $filters): Collection
    {
        $q = ManufacturingPlanMaterial::with(['plan.productVariation', 'productVariation', 'warehouse', 'product.category'])
            ->whereHas('plan', function ($p) use ($filters) {
                $p->where('is_deleted', 0);
                if (!empty($filters['business_id'])) {
                    $p->where('business_id', $filters['business_id']);
                }
                if (!empty($filters['branch_id'])) {
                    $p->where('branch_id', $filters['branch_id']);
                }
                if (!empty($filters['manufacturing_plan_id'])) {
                    $p->where('manufacturing_plan_id', $filters['manufacturing_plan_id']);
                }
                applyRoleScope($p, $this->allow_roles);
            });

        if (!empty($filters['product_id'])) {
            $q->where('product_id', $filters['product_id']);
        }
        if (!empty($filters['product_variation_id'])) {
            $q->where('product_variation_id', $filters['product_variation_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $q->where('warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['start_date'])) {
            $q->whereHas('plan', fn ($p) => $p->where('plan_date', '>=', Carbon::parse($filters['start_date'])->startOfDay()));
        }
        if (!empty($filters['end_date'])) {
            $q->whereHas('plan', fn ($p) => $p->where('plan_date', '<=', Carbon::parse($filters['end_date'])->endOfDay()));
        }

        return $q->get()->map(function ($item) {
            $expected = (float) $item->required_base_quantity;
            $actual = (float) $item->consumed_quantity;
            $variance = $actual - $expected;
            $variance_pct = $expected > 0 ? round(($variance / $expected) * 100, 2) : null;
            $efficiency = $actual > 0 ? round(($expected / $actual) * 100, 2) : null;

            $avgCost = (float) ProductionConsumption::whereHas('production', function ($p) use ($item) {
                $p->where('manufacturing_plan_id', $item->manufacturing_plan_id)->where('is_deleted', 0);
            })->where('product_variation_id', $item->product_variation_id)->avg('unit_cost');

            return (object) [
                'date_created' => $item->plan?->plan_date,
                'group_label' => $item->productVariation?->name ?? '-',
                'raw_material_name' => $item->productVariation?->name ?? '-',
                'finished_product' => $item->plan?->productVariation?->name ?? '-',
                'batch_no' => '-',
                'base_quantity' => $actual,
                'expected_qty' => $expected,
                'variance_qty' => $variance,
                'variance_pct' => $variance_pct,
                'efficiency_pct' => $efficiency,
                'unit_cost' => $avgCost,
                'total_cost' => round($actual * $avgCost, 2),
                'warehouse_name' => $item->warehouse?->name ?? '-',
                'production_no' => '-',
                'plan_no' => $item->plan?->plan_no ?? '-',
                'recipe_id' => $item->plan?->product_recipe_id,
                'production_url' => null,
                'plan_url' => $item->manufacturing_plan_id
                    ? url('/admin/manufacturing-plan/edit/' . $item->manufacturing_plan_id)
                    : null,
                'line_count' => 1,
            ];
        });
    }

    public function getData($filters)
    {
        $rows = $this->build($filters);
        $totals = [
            'total_qty' => decimal(round($rows->sum('base_quantity'), 3)),
            'total_cost' => currency(round($rows->sum('total_cost'), 2)),
            'total_expected' => decimal(round($rows->sum(fn ($r) => (float) ($r->expected_qty ?? 0)), 3)),
            'total_variance' => decimal(round($rows->sum(fn ($r) => (float) ($r->variance_qty ?? 0)), 3)),
        ];

        return DataTables::of($rows)
            ->addColumn('date', fn ($item) => $item->date_created ? localDate($item->date_created) : '-')
            ->addColumn('group_label', fn ($item) => e($item->group_label))
            ->addColumn('raw_material', fn ($item) => e($item->raw_material_name))
            ->addColumn('finished_product', fn ($item) => e($item->finished_product))
            ->addColumn('batch_consumed', fn ($item) => e($item->batch_no))
            ->addColumn('quantity', fn ($item) => decimal($item->base_quantity))
            ->addColumn('expected_qty', fn ($item) => $item->expected_qty !== null ? decimal($item->expected_qty) : '-')
            ->addColumn('variance_qty', fn ($item) => $item->variance_qty !== null ? decimal($item->variance_qty) : '-')
            ->addColumn('variance_pct', fn ($item) => $item->variance_pct !== null ? $item->variance_pct . '%' : '-')
            ->addColumn('efficiency_pct', fn ($item) => $item->efficiency_pct !== null ? $item->efficiency_pct . '%' : '-')
            ->addColumn('unit_cost', fn ($item) => currency($item->unit_cost))
            ->addColumn('total_cost', fn ($item) => currency($item->total_cost))
            ->addColumn('warehouse', fn ($item) => e($item->warehouse_name))
            ->addColumn('production_no', function ($item) {
                if ($item->production_url) {
                    return '<a href="' . e($item->production_url) . '">' . e($item->production_no) . '</a>';
                }
                return e($item->production_no);
            })
            ->addColumn('plan_no', function ($item) {
                if ($item->plan_url) {
                    return '<a href="' . e($item->plan_url) . '">' . e($item->plan_no) . '</a>';
                }
                return e($item->plan_no);
            })
            ->addColumn('line_count', fn ($item) => $item->line_count)
            ->rawColumns(['production_no', 'plan_no'])
            ->with($totals)
            ->make(true);
    }
}
