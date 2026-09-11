<?php

namespace App\Http\Controllers\Admin\Reports\Inventory;

use App\Exports\InventoryReportExport;
use App\Models\User;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\Reports\Inventory\CostPriceAdjustmentReportService;
use App\Services\Concrete\Admin\WarehouseService;
use Illuminate\Http\Request;

class CostPriceAdjustmentReportController extends BaseInventoryReportController
{
    public function __construct(
        CostPriceAdjustmentReportService $service,
        protected BusinessService $business_service,
        protected BranchService $branch_service,
        protected WarehouseService $warehouse_service,
        protected ProductService $product_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->service = $service;
        parent::__construct($print_setting_resolver, $document_send_log_service);
    }

    protected function permissionName(): string { return 'reports.cost-price-adjustment.view'; }
    protected function reportKey(): string { return 'cost-price-adjustment'; }
    protected function viewDir(): string { return 'cost_price_adjustment'; }

    protected function exportInstance($rows)
    {
        return new InventoryReportExport(
            $rows,
            ['Reference No', 'Date', 'Product', 'Variation', 'Warehouse', 'Branch', 'Previous Cost', 'New Cost', 'Qty', 'Difference/Unit', 'Total Adjustment', 'Reason', 'Status', 'Created By', 'Approved By', 'JV No.'],
            ['reference_no', 'adjustment_date', 'product_name', 'variation_name', 'warehouse_name', 'branch_name', 'previous_cost_price', 'new_cost_price', 'quantity_on_hand', 'difference_per_unit', 'total_adjustment_amount', 'reason', 'status', 'created_by_name', 'approved_by_name', 'journal_entry_no']
        );
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $products = $this->product_service->getAllActive();
        $users = User::where('is_deleted', 0)->orderBy('name')->get(['id', 'name']);
        $report_title = 'Cost Price Adjustment Report';

        return view('admin.reports.inventory.cost_price_adjustment.index', compact(
            'business', 'branches', 'warehouses', 'products', 'users', 'report_title'
        ));
    }
}
