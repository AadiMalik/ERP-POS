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
use App\Services\Concrete\Admin\Reports\Inventory\StockSummaryReportService;
use App\Services\Concrete\Admin\WarehouseService;
use Illuminate\Http\Request;

class StockSummaryReportController extends BaseInventoryReportController
{
    public function __construct(
        StockSummaryReportService $service,
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

    protected function permissionName(): string
    {
        return 'reports.stock-summary.view';
    }

    protected function reportKey(): string
    {
        return 'stock-summary';
    }

    protected function viewDir(): string
    {
        return 'stock_summary';
    }

    protected function exportInstance($rows)
    {
        return new InventoryReportExport(
            $rows,
            ['Product', 'Variation', 'Warehouse', 'Branch', 'Category', 'Qty', 'Reserved', 'Available', 'Avg Price', 'Value', 'Min Stock', 'Reorder'],
            ['product_name', 'variation_name', 'warehouse_name', 'branch_name', 'category_name', 'quantity', 'reserved_quantity', 'available_quantity', 'avg_price', 'stock_value', 'minimum_stock', 'reorder_qty']
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
        $report_title = 'Stock Summary Report';
        $preset_mode = $request->get('report_mode', 'summary');

        return view('admin.reports.inventory.stock_summary.index', compact(
            'business', 'branches', 'warehouses', 'products', 'categories', 'brands', 'report_title', 'preset_mode'
        ));
    }
}
