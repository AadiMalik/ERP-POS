<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance;

use App\Enums\RoleNames;
use App\Exports\Hrm\LeavePayrollImpactReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance\LeavePayrollImpactReportService;
use Illuminate\Http\Request;

class LeavePayrollImpactReportController extends BasePayrollFinanceReportController
{
    public function __construct(
        LeavePayrollImpactReportService $service,
        protected DepartmentService $department_service,
        protected BusinessService $business_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.leave-payroll-impact-report.view';
    }

    protected function reportKey(): string
    {
        return 'leave-payroll-impact-report';
    }

    protected function viewDir(): string
    {
        return 'leave_payroll_impact_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();

        return view('admin.reports.leave_payroll_impact_report.index', compact('business', 'departments'));
    }

    protected function exportInstance($rows)
    {
        return new LeavePayrollImpactReportExport($rows);
    }
}
