<?php

namespace App\Http\Controllers\Admin\Reports\Inventory;

use App\Enums\TransactionType;
use App\Exports\InventoryReportExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BrandService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CategoryService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\Reports\Inventory\StockLossReportService;
use App\Services\Concrete\Admin\WarehouseService;
use Illuminate\Http\Request;

class StockLossReportController extends BaseInventoryReportController
{
    public function __construct(
        StockLossReportService $service,
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

    protected function permissionName(): string { return 'reports.stock-loss.view'; }
    protected function reportKey(): string { return 'stock-loss'; }
    protected function viewDir(): string { return 'stock_loss'; }

    protected function exportInstance($rows)
    {
        return new InventoryReportExport(
            $rows,
            ['Date', 'Type', 'Source', 'Reference', 'Warehouse', 'Product', 'Variation', 'Qty', 'Unit Cost', 'Value'],
            ['transaction_date', 'transaction_type_label', 'source_module', 'reference_no', 'warehouse_name', 'product_name', 'variation_name', 'quantity', 'unit_price', 'value']
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
        $report_title = 'Stock Loss / Wastage / Damage Report';
        $preset_mode = $request->get('report_mode');
        $loss_types = [
            TransactionType::DAMAGE => 'Damage',
            TransactionType::WASTAGE => 'Wastage',
            TransactionType::EXPIRED => 'Expired',
        ];

        return view('admin.reports.inventory.stock_loss.index', compact(
            'business', 'branches', 'warehouses', 'products', 'categories', 'brands', 'report_title', 'preset_mode', 'loss_types'
        ));
    }
}
