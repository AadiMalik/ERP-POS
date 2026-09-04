<?php

namespace App\Http\Controllers\Admin\Reports\Inventory;

use App\Enums\SerialMovementEventType;
use App\Exports\InventoryReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\Reports\Inventory\SerialNumberMovementReportService;
use Illuminate\Http\Request;

class SerialNumberMovementReportController extends BaseInventoryReportController
{
    public function __construct(
        SerialNumberMovementReportService $service,
        protected BusinessService $business_service,
        protected ProductService $product_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->service = $service;
        parent::__construct($print_setting_resolver, $document_send_log_service);
    }

    protected function permissionName(): string
    {
        return 'reports.serial-number-movement.view';
    }

    protected function reportKey(): string
    {
        return 'serial-number-movement';
    }

    protected function viewDir(): string
    {
        return 'serial_number_movement';
    }

    protected function exportInstance($rows)
    {
        return new InventoryReportExport(
            $rows,
            ['Date', 'Serial No', 'Product', 'Variation', 'Event', 'From', 'To', 'By', 'Notes'],
            ['date_created', 'serial_no', 'product_name', 'variation_name', 'event_label', 'from_warehouse_name', 'to_warehouse_name', 'createdby_name', 'notes']
        );
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $event_types = SerialMovementEventType::getOptions();
        $report_title = 'Serial Number Movement History';

        return view('admin.reports.inventory.serial_number_movement.index', compact(
            'business', 'products', 'event_types', 'report_title'
        ));
    }
}
