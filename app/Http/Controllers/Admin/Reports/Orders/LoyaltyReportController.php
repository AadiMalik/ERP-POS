<?php

namespace App\Http\Controllers\Admin\Reports\Orders;

use App\Enums\RoleNames;
use App\Exports\Orders\LoyaltyReportExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Orders\LoyaltyReportService;
use Illuminate\Http\Request;

class LoyaltyReportController extends BaseOrderReportController
{
    public function __construct(
        LoyaltyReportService $service,
        protected BranchService $branch_service,
        protected CustomerService $customer_service,
        protected BusinessService $business_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.loyalty-report.view';
    }

    protected function reportKey(): string
    {
        return 'loyalty-report';
    }

    protected function viewDir(): string
    {
        return 'loyalty_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $branches = $this->branch_service->getAllActive();
        $customers = $this->customer_service->getAllActive();

        return view('admin.reports.loyalty_report.index', compact('business', 'branches', 'customers'));
    }

    protected function exportInstance($rows)
    {
        return new LoyaltyReportExport($rows);
    }
}
