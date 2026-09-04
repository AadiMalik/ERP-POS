<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Models\ProductionConsumption;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

/**
 * Raw-material-centric: answers "where was X consumed, by which production/
 * plan, from which batch" - backward traceability from a raw material to the
 * productions/finished-goods batches it fed.
 */
class MaterialConsumptionReportService
{
    protected function query(array $filters)
    {
        // production_consumptions has no business_id/branch_id of its own -
        // role/business/branch scoping is applied to the related Production
        // row inside whereHas(), not to this table directly.
        $allow_roles = [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN, RoleNames::INVENTORYMANAGER, RoleNames::BRANCHADMIN];

        $q = ProductionConsumption::with(['product', 'productVariation', 'batch', 'warehouse', 'production.plan', 'production.warehouse'])
            ->whereHas('production', function ($p) use ($filters, $allow_roles) {
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
        if (!empty($filters['start_date'])) {
            $q->where('date_created', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $q->where('date_created', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        return $q;
    }

    public function getData($filters)
    {
        return DataTables::of($this->query($filters)->orderByDesc('date_created'))
            ->addColumn('date', fn ($item) => localDate($item->date_created))
            ->addColumn('raw_material', fn ($item) => $item->productVariation?->name ?? '-')
            ->addColumn('batch_consumed', fn ($item) => $item->batch?->batch_no ?? '-')
            ->addColumn('quantity', fn ($item) => decimal($item->base_quantity))
            ->addColumn('unit_cost', fn ($item) => currency($item->unit_cost))
            ->addColumn('total_cost', fn ($item) => currency($item->total_cost))
            ->addColumn('warehouse', fn ($item) => $item->warehouse?->name ?? '-')
            ->addColumn('production_no', fn ($item) => $item->production?->production_no ?? '-')
            ->addColumn('plan_no', fn ($item) => $item->production?->plan?->plan_no ?? '-')
            ->make(true);
    }

    public function build(array $filters)
    {
        return $this->query($filters)->orderByDesc('date_created')->get()->map(function ($item) {
            return (object) [
                'date_created' => $item->date_created,
                'raw_material_name' => $item->productVariation?->name ?? '-',
                'batch_no' => $item->batch?->batch_no ?? '-',
                'base_quantity' => $item->base_quantity,
                'unit_cost' => $item->unit_cost,
                'total_cost' => $item->total_cost,
                'warehouse_name' => $item->warehouse?->name ?? '-',
                'production_no' => $item->production?->production_no ?? '-',
                'plan_no' => $item->production?->plan?->plan_no ?? '-',
            ];
        });
    }
}
