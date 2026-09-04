<?php

namespace App\Http\Controllers\Admin\Reports\Inventory;

use App\Exports\InventoryReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\Reports\Inventory\SerialNumberCustomerReportService;
use Illuminate\Http\Request;

class SerialNumberCustomerReportController extends BaseInventoryReportController
{
    public function __construct(
        SerialNumberCustomerReportService $service,
        protected BusinessService $business_service,
        protected CustomerService $customer_service,
        protected ProductService $product_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->service = $service;
        parent::__construct($print_setting_resolver, $document_send_log_service);
    }

    protected function permissionName(): string
    {
        return 'reports.serial-number-customer.view';
    }

    protected function reportKey(): string
    {
        return 'serial-number-customer';
    }

    protected function viewDir(): string
    {
        return 'serial_number_customer';
    }

    protected function exportInstance($rows)
    {
        return new InventoryReportExport(
            $rows,
            ['Customer', 'Serial No', 'Product', 'Variation', 'Order #', 'Warranty Until'],
            ['customer_name', 'serial_no', 'product_name', 'variation_name', 'order_daily_id', 'warranty_expires_at']
        );
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $customers = $this->customer_service->getAllActive();
        $products = $this->product_service->getAllActive();
        $report_title = 'Customer-wise Serial Numbers';

        return view('admin.reports.inventory.serial_number_customer.index', compact(
            'business', 'customers', 'products', 'report_title'
        ));
    }
}
