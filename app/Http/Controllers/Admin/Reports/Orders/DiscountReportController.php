<?php

namespace App\Http\Controllers\Admin\Reports\Orders;

use App\Enums\RoleNames;
use App\Exports\Orders\DiscountReportExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\OrderSourceService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Orders\DiscountReportService;
use Illuminate\Http\Request;

class DiscountReportController extends BaseOrderReportController
{
    public function __construct(
        DiscountReportService $service,
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
        return 'reports.discount-report.view';
    }

    protected function reportKey(): string
    {
        return 'discount-report';
    }

    protected function viewDir(): string
    {
        return 'discount_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $branches = $this->branch_service->getAllActive();
        $order_sources = $this->order_source_service->getAllActive();

        return view('admin.reports.discount_report.index', compact('business', 'branches', 'order_sources'));
    }

    protected function exportInstance($rows)
    {
        return new DiscountReportExport($rows);
    }
}
