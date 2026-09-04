<?php

namespace App\Http\Controllers\Admin\Reports\Inventory;

use App\Exports\InventoryReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\Reports\Inventory\SerialNumberReportService;
use App\Services\Concrete\Admin\WarehouseService;
use Illuminate\Http\Request;

class SerialNumberRegisterReportController extends BaseInventoryReportController
{
    public function __construct(
        SerialNumberReportService $service,
        protected BusinessService $business_service,
        protected WarehouseService $warehouse_service,
        protected ProductService $product_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->service = $service;
        parent::__construct($print_setting_resolver, $document_send_log_service);
    }

    protected function permissionName(): string
    {
        return 'reports.serial-number-register.view';
    }

    protected function reportKey(): string
    {
        return 'serial-number-register';
    }

    protected function viewDir(): string
    {
        return 'serial_number_register';
    }

    protected function exportInstance($rows)
    {
        return new InventoryReportExport(
            $rows,
            ['Serial No', 'Product', 'Variation', 'Warehouse', 'Status', 'Unit Cost', 'Customer', 'Received On'],
            ['serial_no', 'product_name', 'variation_name', 'warehouse_name', 'status_label', 'avg_price', 'customer_name', 'date_created']
        );
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $warehouses = $this->warehouse_service->getAllActive();
        $products = $this->product_service->getAllActive();
        $report_title = 'Serial Number Register';

        return view('admin.reports.inventory.serial_number_register.index', compact(
            'business', 'warehouses', 'products', 'report_title'
        ));
    }
}
