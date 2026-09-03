<?php

namespace App\Http\Controllers\Admin\Reports\Orders;

use App\Enums\RoleNames;
use App\Exports\Orders\CustomerSalesReportExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\OrderSourceService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Orders\CustomerSalesReportService;
use Illuminate\Http\Request;

class CustomerSalesReportController extends BaseOrderReportController
{
    public function __construct(
        CustomerSalesReportService $service,
        protected BranchService $branch_service,
        protected OrderSourceService $order_source_service,
        protected BusinessService $business_service,
        protected CustomerService $customer_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.customer-sales.view';
    }

    protected function reportKey(): string
    {
        return 'customer-sales';
    }

    protected function viewDir(): string
    {
        return 'customer_sales';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $branches = $this->branch_service->getAllActive();
        $order_sources = $this->order_source_service->getAllActive();
        $customers = $this->customer_service->getAllActive();

        return view('admin.reports.customer_sales.index', compact('business', 'branches', 'order_sources', 'customers'));
    }

    protected function exportInstance($rows)
    {
        return new CustomerSalesReportExport($rows);
    }
}
