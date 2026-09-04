<?php

namespace App\Http\Controllers\Admin\Reports\Inventory;

use App\Enums\LossType;
use App\Enums\Status;
use App\Exports\InventoryReportExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\LossReasonService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\Reports\Inventory\WasteDamageExpiryReportService;
use App\Services\Concrete\Admin\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WasteDamageExpiryReportController extends BaseInventoryReportController
{
    public function __construct(
        WasteDamageExpiryReportService $service,
        protected BusinessService $business_service,
        protected BranchService $branch_service,
        protected WarehouseService $warehouse_service,
        protected ProductService $product_service,
        protected LossReasonService $loss_reason_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->service = $service;
        parent::__construct($print_setting_resolver, $document_send_log_service);
    }

    protected function permissionName(): string { return 'reports.waste-damage-expiry.view'; }
    protected function reportKey(): string { return 'waste-damage-expiry'; }
    protected function viewDir(): string { return 'waste_damage_expiry'; }

    protected function exportInstance($rows)
    {
        return new InventoryReportExport(
            $rows,
            ['Reference No', 'Date', 'Warehouse', 'Product', 'Variation', 'Batch', 'Expiry', 'Qty', 'Unit', 'Unit Cost', 'Value', 'Loss Type', 'Reason', 'Status', 'Created By', 'Approved By'],
            ['reference_no', 'transaction_date', 'warehouse_name', 'product_name', 'variation_name', 'batch_no', 'expiry_date', 'quantity', 'unit_name', 'unit_cost', 'value', 'loss_type_label', 'loss_reason', 'status', 'created_by', 'approved_by']
        );
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $products = $this->product_service->getAllActive();
        $loss_reasons = $this->loss_reason_service->getActiveByBusiness(Auth::user()->business_id);
        $loss_types = LossType::getOptions();
        $statuses = [
            Status::PENDING   => ucfirst(Status::PENDING),
            Status::APPROVED  => ucfirst(Status::APPROVED),
            Status::CANCELLED => ucfirst(Status::CANCELLED),
        ];
        $report_title = 'Waste / Damage / Expiry Report';

        return view('admin.reports.inventory.waste_damage_expiry.index', compact(
            'business', 'branches', 'warehouses', 'products', 'loss_reasons', 'loss_types', 'statuses', 'report_title'
        ));
    }
}
