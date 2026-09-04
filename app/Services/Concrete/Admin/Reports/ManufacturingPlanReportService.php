<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\ManufacturingPlanStatus;
use App\Enums\RoleNames;
use App\Models\ManufacturingPlan;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

class ManufacturingPlanReportService
{
    protected function query(array $filters)
    {
        $q = ManufacturingPlan::with(['business', 'branch', 'product', 'productVariation'])
            ->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $q->where('business_id', $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $q->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['product_id'])) {
            $q->where('product_id', $filters['product_id']);
        }
        if (!empty($filters['status'])) {
            $q->where('status', $filters['status']);
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
        $labels = ManufacturingPlanStatus::getOptions();

        return DataTables::of($this->query($filters)->orderByDesc('date_created'))
            ->addColumn('business', fn ($item) => $item->business?->name ?? '-')
            ->addColumn('branch', fn ($item) => $item->branch?->name ?? '-')
            ->addColumn('product', fn ($item) => $item->productVariation?->name ?? $item->product?->name ?? '-')
            ->addColumn('plan_date', fn ($item) => $item->plan_date ? localDate($item->plan_date) : '-')
            ->addColumn('planned_quantity', fn ($item) => decimal($item->planned_quantity))
            ->addColumn('produced_quantity', fn ($item) => decimal($item->produced_quantity))
            ->addColumn('remaining_quantity', fn ($item) => decimal($item->remaining_quantity))
            ->addColumn('progress', fn ($item) => $item->progress_percentage . '%')
            ->addColumn('status', fn ($item) => $labels[$item->status] ?? $item->status)
            ->make(true);
    }

    public function build(array $filters)
    {
        return $this->query($filters)->orderByDesc('date_created')->get()->map(function ($item) {
            return (object) [
                'plan_no' => $item->plan_no,
                'plan_date' => $item->plan_date,
                'business_name' => $item->business?->name ?? '-',
                'branch_name' => $item->branch?->name ?? '-',
                'product_name' => $item->productVariation?->name ?? $item->product?->name ?? '-',
                'planned_quantity' => $item->planned_quantity,
                'produced_quantity' => $item->produced_quantity,
                'remaining_quantity' => $item->remaining_quantity,
                'progress_percentage' => $item->progress_percentage,
                'status_label' => ManufacturingPlanStatus::getOptions()[$item->status] ?? $item->status,
                'date_created' => $item->date_created,
            ];
        });
    }
}
