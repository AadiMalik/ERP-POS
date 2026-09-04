<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\ProductionStatus;
use App\Enums\RoleNames;
use App\Models\Production;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

/**
 * Doubles as Production/Batch History and Production Cost Report (cost
 * columns) - see the plan's "Reports are consolidated to 3 controllers, not
 * 6" decision.
 */
class ProductionReportService
{
    protected function query(array $filters)
    {
        $q = Production::with(['business', 'branch', 'plan.productVariation', 'warehouse', 'recipe'])
            ->where('is_deleted', 0);

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

        $allow_roles = [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN, RoleNames::INVENTORYMANAGER, RoleNames::BRANCHADMIN];
        return applyRoleScope($q, $allow_roles);
    }

    public function getData($filters)
    {
        $labels = ProductionStatus::getOptions();

        return DataTables::of($this->query($filters)->orderByDesc('date_created'))
            ->addColumn('business', fn ($item) => $item->business?->name ?? '-')
            ->addColumn('branch', fn ($item) => $item->branch?->name ?? '-')
            ->addColumn('plan_no', fn ($item) => $item->plan?->plan_no ?? '-')
            ->addColumn('product', fn ($item) => $item->plan?->productVariation?->name ?? '-')
            ->addColumn('warehouse', fn ($item) => $item->warehouse?->name ?? '-')
            ->addColumn('quantity', fn ($item) => decimal($item->quantity))
            ->addColumn('material_cost', fn ($item) => currency($item->material_cost))
            ->addColumn('total_cost', fn ($item) => currency($item->total_cost))
            ->addColumn('unit_cost', fn ($item) => currency($item->unit_cost))
            ->addColumn('status', fn ($item) => $labels[$item->status] ?? $item->status)
            ->make(true);
    }

    public function build(array $filters)
    {
        return $this->query($filters)->orderByDesc('date_created')->get()->map(function ($item) {
            return (object) [
                'production_no' => $item->production_no,
                'plan_no' => $item->plan?->plan_no ?? '-',
                'business_name' => $item->business?->name ?? '-',
                'branch_name' => $item->branch?->name ?? '-',
                'product_name' => $item->plan?->productVariation?->name ?? '-',
                'warehouse_name' => $item->warehouse?->name ?? '-',
                'batch_no' => $item->batch_no,
                'manufacturing_date' => $item->manufacturing_date,
                'expiry_date' => $item->expiry_date,
                'quantity' => $item->quantity,
                'material_cost' => $item->material_cost,
                'labor_cost' => $item->labor_cost,
                'overhead_cost' => $item->overhead_cost,
                'other_cost' => $item->other_cost,
                'total_cost' => $item->total_cost,
                'unit_cost' => $item->unit_cost,
                'status_label' => ProductionStatus::getOptions()[$item->status] ?? $item->status,
            ];
        });
    }
}
