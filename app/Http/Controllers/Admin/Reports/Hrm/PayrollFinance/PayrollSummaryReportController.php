<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance;

use App\Enums\RoleNames;
use App\Exports\Hrm\PayrollSummaryReportExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance\PayrollSummaryReportService;
use Illuminate\Http\Request;

class PayrollSummaryReportController extends BasePayrollFinanceReportController
{
    public function __construct(
        PayrollSummaryReportService $service,
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
        return 'reports.payroll-summary-report.view';
    }

    protected function reportKey(): string
    {
        return 'payroll-summary-report';
    }

    protected function viewDir(): string
    {
        return 'payroll_summary_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $branches = $this->branch_service->getAllActive();

        return view('admin.reports.payroll_summary_report.index', compact('business', 'branches'));
    }

    protected function exportInstance($rows)
    {
        return new PayrollSummaryReportExport($rows);
    }
}
