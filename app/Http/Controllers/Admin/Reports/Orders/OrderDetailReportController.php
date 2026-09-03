<?php

namespace App\Http\Controllers\Admin\Reports\Orders;

use App\Enums\RoleNames;
use App\Exports\Orders\OrderDetailReportExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\OrderSourceService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Orders\OrderDetailReportService;
use Illuminate\Http\Request;

class OrderDetailReportController extends BaseOrderReportController
{
    public function __construct(
        OrderDetailReportService $service,
        protected BranchService $branch_service,
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
        return 'reports.order-detail.view';
    }

    protected function reportKey(): string
    {
        return 'order-detail';
    }

    protected function viewDir(): string
    {
        return 'order_detail';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $branches = $this->branch_service->getAllActive();
        $order_sources = $this->order_source_service->getAllActive();

        return view('admin.reports.order_detail.index', compact('business', 'branches', 'order_sources'));
    }

    protected function exportInstance($rows)
    {
        return new OrderDetailReportExport($rows);
    }
}
