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

class SerialNumberSoldReportController extends BaseInventoryReportController
{
    public function __construct(
        protected BusinessService $business_service,
        protected WarehouseService $warehouse_service,
        protected ProductService $product_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->service = new SerialNumberReportService(SerialStatus::SOLD);
        parent::__construct($print_setting_resolver, $document_send_log_service);
    }

    protected function permissionName(): string
    {
        return 'reports.serial-number-sold.view';
    }

    protected function reportKey(): string
    {
        return 'serial-number-sold';
    }

    protected function viewDir(): string
    {
        return 'serial_number_sold';
    }

    protected function exportInstance($rows)
    {
        return new InventoryReportExport(
            $rows,
            ['Serial No', 'Product', 'Variation', 'Customer', 'Order #', 'Unit Cost'],
            ['serial_no', 'product_name', 'variation_name', 'customer_name', 'order_daily_id', 'avg_price']
        );
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $warehouses = $this->warehouse_service->getAllActive();
        $products = $this->product_service->getAllActive();
        $report_title = 'Sold Serial Numbers';

        return view('admin.reports.inventory.serial_number_sold.index', compact(
            'business', 'warehouses', 'products', 'report_title'
        ));
    }
}
