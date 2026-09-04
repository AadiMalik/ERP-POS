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
use App\Services\Concrete\Admin\Reports\Inventory\BatchExpiryReportService;
use App\Services\Concrete\Admin\WarehouseService;
use Illuminate\Http\Request;

class BatchExpiryReportController extends BaseInventoryReportController
{
    public function __construct(
        BatchExpiryReportService $service,
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

    protected function permissionName(): string { return 'reports.batch-expiry.view'; }
    protected function reportKey(): string { return 'batch-expiry'; }
    protected function viewDir(): string { return 'batch_expiry'; }

    protected function exportInstance($rows)
    {
        return new InventoryReportExport(
            $rows,
            ['Batch', 'Product', 'Variation', 'Warehouse', 'Branch', 'Qty', 'Avg Price', 'Value', 'Mfg Date', 'Expiry', 'Days to Expiry', 'Status'],
            ['batch_no', 'product_name', 'variation_name', 'warehouse_name', 'branch_name', 'quantity', 'avg_price', 'stock_value', 'manufacturing_date', 'expiry_date', 'days_to_expiry', 'status']
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
        $report_title = 'Batch/Lot & Expiry Report';
        $preset_mode = $request->get('report_mode', 'batch_stock');

        return view('admin.reports.inventory.batch_expiry.index', compact(
            'business', 'branches', 'warehouses', 'products', 'categories', 'brands', 'report_title', 'preset_mode'
        ));
    }
}
