<?php

namespace App\Traits;

use App\Enums\Status;
use App\Models\Warehouse;
use Exception;

/**
 * A concrete, active, correctly-owned warehouse must always be resolved
 * before a transaction can commit stock against it. Extracted from
 * OrderService::assertValidWarehouse() so Sales and the new Manufacturing
 * services (a Manufacturing Plan's reservation warehouse, a Production's
 * finished-goods warehouse) share one implementation instead of two copies
 * drifting apart.
 */
trait ValidatesWarehouse
{
    protected function assertValidWarehouse(?string $businessId, ?string $branchId, ?string $warehouseId): void
    {
        if (empty($businessId) || empty($branchId) || empty($warehouseId)) {
            throw new Exception('A valid warehouse could not be determined for this transaction.');
        }

        // A Warehouse's Branch is optional (see resources/views/admin/warehouse/
        // create.blade.php - no branch means it's shared across every branch
        // of the business), so a null branch_id on the warehouse is valid for
        // any branch, not just an exact match.
        $warehouse = Warehouse::where('warehouse_id', $warehouseId)
            ->where('business_id', $businessId)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })
            ->where('is_deleted', 0)
            ->first();

        if (!$warehouse) {
            throw new Exception('The selected warehouse does not exist or does not belong to this business/branch.');
        }

        if ($warehouse->status !== Status::ACTIVE) {
            throw new Exception('The selected warehouse "' . $warehouse->name . '" is inactive and cannot be used.');
        }
    }

    /**
     * A branch-scoped sale/order no longer picks one warehouse - it draws
     * combined stock from every warehouse the branch is linked to (see
     * Branch::warehouses(), branch_warehouses pivot). This just asserts that
     * link exists before a transaction proceeds, and returns the linked
     * warehouse ids (priority-ordered) for the caller to consume.
     */
    protected function assertBranchHasLinkedWarehouses(?string $businessId, ?string $branchId): array
    {
        if (empty($businessId) || empty($branchId)) {
            throw new Exception('A valid branch could not be determined for this transaction.');
        }

        $warehouseIds = app(\App\Services\Concrete\Admin\ProductVariationStockService::class)
            ->getLinkedWarehouseIds($businessId, $branchId);

        if (empty($warehouseIds)) {
            throw new Exception('No active warehouse is linked to this branch. Link at least one warehouse to it before selling.');
        }

        return $warehouseIds;
    }
}
