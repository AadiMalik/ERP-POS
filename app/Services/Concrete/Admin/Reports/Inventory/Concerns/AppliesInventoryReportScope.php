<?php

namespace App\Services\Concrete\Admin\Reports\Inventory\Concerns;

use App\Enums\RoleNames;
use Illuminate\Support\Facades\Auth;

trait AppliesInventoryReportScope
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::INVENTORYMANAGER,
        RoleNames::BRANCHADMIN,
        RoleNames::REPORTINGANALYST,
        RoleNames::PURCHASEMANAGER,
        RoleNames::FINANCEMANAGER,
    ];

    protected function businessId(array $obj): ?string
    {
        return $obj['business_id'] ?? Auth::user()->business_id;
    }

    protected function baseFilters(array $obj): array
    {
        return [
            'business_id'          => $this->businessId($obj),
            'branch_id'            => $obj['branch_id'] ?? null,
            'warehouse_id'         => $obj['warehouse_id'] ?? null,
            'product_id'           => $obj['product_id'] ?? null,
            'product_variation_id' => $obj['product_variation_id'] ?? null,
            'category_id'          => $obj['category_id'] ?? null,
            'sub_category_id'      => $obj['sub_category_id'] ?? null,
            'brand_id'             => $obj['brand_id'] ?? null,
            'report_mode'          => $obj['report_mode'] ?? null,
            'start_date'           => $obj['start_date'] ?? null,
            'end_date'             => $obj['end_date'] ?? null,
            'status'               => $obj['status'] ?? null,
            'allow_roles'          => $this->allow_roles,
        ];
    }
}
