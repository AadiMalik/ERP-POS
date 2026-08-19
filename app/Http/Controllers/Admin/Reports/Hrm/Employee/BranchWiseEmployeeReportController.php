<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Employee;

use App\Enums\RoleNames;
use App\Exports\Hrm\BranchWiseEmployeeReportExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Employee\BranchWiseEmployeeReportService;

class BranchWiseEmployeeReportController extends BaseEmployeeReportController
{
    public function __construct(
        BranchWiseEmployeeReportService $service,
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
        return 'reports.branch-wise-employee-report.view';
    }

    protected function reportKey(): string
    {
        return 'branch-wise-employee-report';
    }

    protected function viewDir(): string
    {
        return 'branch_wise_employee_report';
    }

    public function index()
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $branches = $this->branch_service->getAllActive();

        return view('admin.reports.branch_wise_employee_report.index', compact('business', 'branches'));
    }

    protected function exportInstance($rows)
    {
        return new BranchWiseEmployeeReportExport($rows);
    }
}
