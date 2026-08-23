<?php

namespace App\Services\Concrete\Admin;

use App\Models\SaleType;
use App\Services\Concrete\Admin\Support\AbstractLookupTypeService;
use Illuminate\Support\Facades\Auth;

class SaleTypeService extends AbstractLookupTypeService
{
    protected function newModelInstance()
    {
        return new SaleType();
    }

    protected function pkField(): string
    {
        return 'sale_type_id';
    }

    protected function domIdSuffix(): string
    {
        return 'SaleType';
    }

    protected function dateFilterEnabled(): bool
    {
        return false;
    }

    protected function defaultRows(): array
    {
        return [
            ['name' => 'Retail', 'code' => 'RETAIL'],
            ['name' => 'Wholesale', 'code' => 'WHOLESALE'],
            ['name' => 'Branch', 'code' => 'BRANCH'],
            ['name' => 'Promotional', 'code' => 'PROMOTIONAL'],
            ['name' => 'Online', 'code' => 'ONLINE'],
        ];
    }

    /**
     * Plain (non-DataTables) list of every sale type for a business,
     * active or inactive - backs the inline Sale Types manager embedded in
     * the POS Settings tab, where a handful of rows don't warrant a full
     * server-side-processed DataTable. Unique to Sale Type - Order Type/
     * Order Source have no equivalent inline-list consumer.
     */
    public function getAll($business_id = null)
    {
        $business_id = $business_id ?? Auth::user()->business_id;
        $this->seedDefaults($business_id);

        return $this->model->getModel()::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name')
            ->get();
    }
}
