<?php

namespace App\Http\Controllers\Admin\Reports\Orders;

use App\Enums\RoleNames;
use App\Exports\Orders\OrderSourceSalesReportExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Orders\OrderSourceSalesReportService;
use Illuminate\Http\Request;

class OrderSourceSalesReportController extends BaseOrderReportController
{
    public function __construct(
        OrderSourceSalesReportService $service,
        protected BranchService $branch_service,
        protected BusinessService $business_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.order-source-sales.view';
    }

    protected function reportKey(): string
    {
        return 'order-source-sales';
    }

    protected function viewDir(): string
    {
        return 'order_source_sales';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $branches = $this->branch_service->getAllActive();

        return view('admin.reports.order_source_sales.index', compact('business', 'branches'));
    }

    protected function exportInstance($rows)
    {
        return new OrderSourceSalesReportExport($rows);
    }
}
