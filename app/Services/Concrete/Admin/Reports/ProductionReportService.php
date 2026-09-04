<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\ProductionStatus;
use App\Enums\RoleNames;
use App\Models\ManufacturingPlanMaterial;
use App\Models\Production;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Production master report — summary/detail, performance/yield, costing,
 * variance, wastage proxy, and traceability via report_mode.
 * No scrap/rework tables exist; wastage is expected-vs-actual material proxy.
 */
class ProductionReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::INVENTORYMANAGER,
        RoleNames::BRANCHADMIN,
        RoleNames::REPORTINGANALYST,
    ];

    protected function query(array $filters)
    {
        $q = Production::with([
            'business',
            'branch',
            'plan.productVariation',
            'plan.materials',
            'warehouse',
            'recipe',
            'consumptions',
            'operator',
        ])->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $q->where('business_id', $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $q->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $q->where('warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['manufacturing_plan_id'])) {
            $q->where('manufacturing_plan_id', $filters['manufacturing_plan_id']);
        }
        if (!empty($filters['product_recipe_id'])) {
            $q->where('product_recipe_id', $filters['product_recipe_id']);
        }
        if (!empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (!empty($filters['batch_no'])) {
            $q->where('batch_no', 'like', '%' . $filters['batch_no'] . '%');
        }
        if (!empty($filters['start_date'])) {
            $q->where('date_created', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $q->where('date_created', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        return applyRoleScope($q, $this->allow_roles);
    }

    public function build(array $filters): Collection
    {
        $labels = ProductionStatus::getOptions();
        $mode = $filters['report_mode'] ?? 'summary';

        return $this->query($filters)->orderByDesc('date_created')->get()->map(function ($item) use ($labels, $mode) {
            $planned = (float) ($item->plan?->planned_quantity ?? 0);
            $produced = (float) $item->quantity;
            // Per-production share of plan for variance display when multiple productions exist
            $planProduced = (float) ($item->plan?->produced_quantity ?? 0);
            $yield_pct = $planned > 0 ? round(($planProduced / $planned) * 100, 2) : null;
            $qty_variance = $planned > 0 ? round($planProduced - $planned, 3) : null;
            $qty_variance_pct = $planned > 0 ? round((($planProduced - $planned) / $planned) * 100, 2) : null;

            $wastage = $this->wastageProxy($item);

            $duration_hours = null;
            if ($item->completed_at && $item->date_created) {
                $duration_hours = round(Carbon::parse($item->date_created)->diffInMinutes(Carbon::parse($item->completed_at)) / 60, 2);
            }

            return (object) [
                'production_id' => $item->production_id,
                'production_no' => $item->production_no,
                'plan_no' => $item->plan?->plan_no ?? '-',
                'business_name' => $item->business?->name ?? '-',
                'branch_name' => $item->branch?->name ?? '-',
                'product_name' => $item->plan?->productVariation?->name ?? '-',
                'warehouse_name' => $item->warehouse?->name ?? '-',
                'batch_no' => $item->batch_no,
                'manufacturing_date' => $item->manufacturing_date,
                'expiry_date' => $item->expiry_date,
                'quantity' => $produced,
                'planned_quantity' => $planned,
                'plan_produced_quantity' => $planProduced,
                'yield_pct' => $yield_pct,
                'qty_variance' => $qty_variance,
                'qty_variance_pct' => $qty_variance_pct,
                'material_cost' => (float) $item->material_cost,
                'labor_cost' => (float) $item->labor_cost,
                'overhead_cost' => (float) $item->overhead_cost,
                'other_cost' => (float) $item->other_cost,
                'total_cost' => (float) $item->total_cost,
                'unit_cost' => (float) $item->unit_cost,
                'expected_material_qty' => $wastage['expected'],
                'actual_material_qty' => $wastage['actual'],
                'wastage_qty' => $wastage['wastage'],
                'wastage_pct' => $wastage['wastage_pct'],
                'consumption_count' => $item->consumptions?->count() ?? 0,
                'duration_hours' => $duration_hours,
                'operator' => $item->operator?->name ?? '-',
                'status_label' => $labels[$item->status] ?? $item->status,
                'recipe_id' => $item->product_recipe_id,
                'production_url' => url('/admin/production/edit/' . $item->production_id),
                'plan_url' => $item->manufacturing_plan_id
                    ? url('/admin/manufacturing-plan/edit/' . $item->manufacturing_plan_id)
                    : null,
                'consumption_url' => url('/admin/reports/material-consumption') . '?' . http_build_query([
                    'production_id' => $item->production_id,
                    'business_id' => $item->business_id,
                ]),
                'ledger_url' => url('/admin/reports/stock-ledger') . '?' . http_build_query([
                    'reference_type' => 'production',
                    'business_id' => $item->business_id,
                ]),
                'report_mode' => $mode,
            ];
        });
    }

    /**
     * Expected material for this production = sum over plan materials of
     * (required_base_quantity / planned_quantity) * production.quantity.
     * Actual = sum of production_consumptions.base_quantity.
     * Positive wastage_qty means over-consumption vs plan rate.
     */
    protected function wastageProxy(Production $item): array
    {
        $planned = (float) ($item->plan?->planned_quantity ?? 0);
        $produced = (float) $item->quantity;
        $expected = 0.0;

        if ($planned > 0 && $item->plan) {
            $materials = $item->plan->materials ?? ManufacturingPlanMaterial::where('manufacturing_plan_id', $item->manufacturing_plan_id)->get();
            foreach ($materials as $mat) {
                $expected += ((float) $mat->required_base_quantity / $planned) * $produced;
            }
        }

        $actual = (float) ($item->consumptions?->sum('base_quantity') ?? 0);
        $wastage = round($actual - $expected, 4);
        $wastage_pct = $expected > 0 ? round(($wastage / $expected) * 100, 2) : null;

        return [
            'expected' => round($expected, 4),
            'actual' => round($actual, 4),
            'wastage' => $wastage,
            'wastage_pct' => $wastage_pct,
        ];
    }

    public function getData($filters)
    {
        $rows = $this->build($filters);
        $totals = [
            'total_qty' => decimal(round($rows->sum('quantity'), 3)),
            'total_cost' => currency(round($rows->sum('total_cost'), 2)),
            'total_material_cost' => currency(round($rows->sum('material_cost'), 2)),
            'total_wastage' => decimal(round($rows->sum('wastage_qty'), 3)),
        ];

        return DataTables::of($rows)
            ->addColumn('production_no', fn ($item) => '<a href="' . e($item->production_url) . '">' . e($item->production_no) . '</a>')
            ->addColumn('plan_no', function ($item) {
                return $item->plan_url
                    ? '<a href="' . e($item->plan_url) . '">' . e($item->plan_no) . '</a>'
                    : e($item->plan_no);
            })
            ->addColumn('business', fn ($item) => e($item->business_name))
            ->addColumn('branch', fn ($item) => e($item->branch_name))
            ->addColumn('product', fn ($item) => e($item->product_name))
            ->addColumn('warehouse', fn ($item) => e($item->warehouse_name))
            ->addColumn('batch_no', fn ($item) => e($item->batch_no ?? '-'))
            ->addColumn('quantity', fn ($item) => decimal($item->quantity))
            ->addColumn('planned_quantity', fn ($item) => decimal($item->planned_quantity))
            ->addColumn('yield_pct', fn ($item) => $item->yield_pct !== null ? $item->yield_pct . '%' : '-')
            ->addColumn('qty_variance', fn ($item) => $item->qty_variance !== null ? decimal($item->qty_variance) : '-')
            ->addColumn('material_cost', fn ($item) => currency($item->material_cost))
            ->addColumn('labor_cost', fn ($item) => currency($item->labor_cost))
            ->addColumn('overhead_cost', fn ($item) => currency($item->overhead_cost))
            ->addColumn('other_cost', fn ($item) => currency($item->other_cost))
            ->addColumn('total_cost', fn ($item) => currency($item->total_cost))
            ->addColumn('unit_cost', fn ($item) => currency($item->unit_cost))
            ->addColumn('expected_material_qty', fn ($item) => decimal($item->expected_material_qty))
            ->addColumn('actual_material_qty', fn ($item) => decimal($item->actual_material_qty))
            ->addColumn('wastage_qty', fn ($item) => decimal($item->wastage_qty))
            ->addColumn('wastage_pct', fn ($item) => $item->wastage_pct !== null ? $item->wastage_pct . '%' : '-')
            ->addColumn('consumption_count', function ($item) {
                return '<a href="' . e($item->consumption_url) . '">' . e($item->consumption_count) . '</a>';
            })
            ->addColumn('duration_hours', fn ($item) => $item->duration_hours !== null ? $item->duration_hours : '-')
            ->addColumn('status', fn ($item) => e($item->status_label))
            ->rawColumns(['production_no', 'plan_no', 'consumption_count'])
            ->with($totals)
            ->make(true);
    }
}
