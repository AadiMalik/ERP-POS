<?php

namespace App\Http\Controllers\Admin\Reports\Inventory;

use App\Enums\SerialStatus;
use App\Exports\InventoryReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\Reports\Inventory\SerialNumberReportService;
use App\Services\Concrete\Admin\WarehouseService;
use Illuminate\Http\Request;

class SerialNumberAvailableReportController extends BaseInventoryReportController
{
    public function __construct(
        protected BusinessService $business_service,
        protected WarehouseService $warehouse_service,
        protected ProductService $product_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->service = new SerialNumberReportService(SerialStatus::AVAILABLE);
        parent::__construct($print_setting_resolver, $document_send_log_service);
    }

    protected function permissionName(): string
    {
        return 'reports.serial-number-available.view';
    }

    protected function reportKey(): string
    {
        return 'serial-number-available';
    }

    protected function viewDir(): string
    {
        return 'serial_number_available';
    }

    protected function exportInstance($rows)
    {
        return new InventoryReportExport(
            $rows,
            ['Serial No', 'Product', 'Variation', 'Warehouse', 'Unit Cost', 'Received On'],
            ['serial_no', 'product_name', 'variation_name', 'warehouse_name', 'avg_price', 'date_created']
        );
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $warehouses = $this->warehouse_service->getAllActive();
        $products = $this->product_service->getAllActive();
        $report_title = 'Available Serial Numbers';

        return view('admin.reports.inventory.serial_number_available.index', compact(
            'business', 'warehouses', 'products', 'report_title'
        ));
    }
}
