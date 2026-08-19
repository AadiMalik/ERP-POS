<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance;

use App\Enums\RoleNames;
use App\Exports\Hrm\BranchPayrollCostReportExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance\BranchPayrollCostReportService;
use Illuminate\Http\Request;

class BranchPayrollCostReportController extends BasePayrollFinanceReportController
{
    public function __construct(
        BranchPayrollCostReportService $service,
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
        return 'reports.branch-payroll-cost-report.view';
    }

    protected function reportKey(): string
    {
        return 'branch-payroll-cost-report';
    }

    protected function viewDir(): string
    {
        return 'branch_payroll_cost_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $branches = $this->branch_service->getAllActive();

        return view('admin.reports.branch_payroll_cost_report.index', compact('business', 'branches'));
    }

    protected function exportInstance($rows)
    {
        return new BranchPayrollCostReportExport($rows);
    }
}
