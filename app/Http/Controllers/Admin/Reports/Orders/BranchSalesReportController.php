<?php

namespace App\Http\Controllers\Admin\Reports\Orders;

use App\Enums\RoleNames;
use App\Exports\Orders\BranchSalesReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\OrderSourceService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Orders\BranchSalesReportService;
use Illuminate\Http\Request;

class BranchSalesReportController extends BaseOrderReportController
{
    public function __construct(
        BranchSalesReportService $service,
        protected OrderSourceService $order_source_service,
        protected BusinessService $business_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.branch-sales.view';
    }

    protected function reportKey(): string
    {
        return 'branch-sales';
    }

    protected function viewDir(): string
    {
        return 'branch_sales';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $order_sources = $this->order_source_service->getAllActive();

        return view('admin.reports.branch_sales.index', compact('business', 'order_sources'));
    }

    protected function exportInstance($rows)
    {
        return new BranchSalesReportExport($rows);
    }
}
