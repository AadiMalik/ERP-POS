<?php

namespace App\Http\Controllers\Admin\Reports\Inventory;

use App\Exports\InventoryReportExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BrandService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CategoryService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\Reports\Inventory\RecipeBomReportService;
use App\Services\Concrete\Admin\WarehouseService;
use Illuminate\Http\Request;

class RecipeBomReportController extends BaseInventoryReportController
{
    public function __construct(
        RecipeBomReportService $service,
        protected BusinessService $business_service,
        protected BranchService $branch_service,
        protected WarehouseService $warehouse_service,
        protected ProductService $product_service,
        protected CategoryService $category_service,
        protected BrandService $brand_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->service = $service;
        parent::__construct($print_setting_resolver, $document_send_log_service);
    }

    protected function permissionName(): string { return 'reports.recipe-bom-report.view'; }
    protected function reportKey(): string { return 'recipe-bom-report'; }
    protected function viewDir(): string { return 'recipe_bom'; }

    protected function exportInstance($rows)
    {
        return new InventoryReportExport(
            $rows,
            ['Finished Product', 'Finished Variation', 'Raw Product', 'Raw Variation', 'Qty', 'Unit', 'Warehouse', 'Unit Cost', 'Line Cost', 'Available', 'Shortfall', 'Has Recipe', 'Updated'],
            ['finished_product', 'finished_variation', 'raw_product', 'raw_variation', 'quantity', 'unit_name', 'warehouse_name', 'unit_cost', 'line_cost', 'available_qty', 'shortfall', 'has_recipe', 'date_updated']
        );
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $products = $this->product_service->getAllActive();
        $categories = $this->category_service->getAllActive();
        $brands = $this->brand_service->getAllActive();
        $report_title = 'Recipe / BOM Report';
        $preset_mode = $request->get('report_mode', 'bom');

        return view('admin.reports.inventory.recipe_bom.index', compact(
            'business', 'branches', 'warehouses', 'products', 'categories', 'brands', 'report_title', 'preset_mode'
        ));
    }
}
