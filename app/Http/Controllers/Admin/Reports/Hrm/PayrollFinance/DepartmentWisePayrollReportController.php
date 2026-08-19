<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance;

use App\Enums\RoleNames;
use App\Exports\Hrm\DepartmentWisePayrollReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance\DepartmentWisePayrollReportService;
use Illuminate\Http\Request;

class DepartmentWisePayrollReportController extends BasePayrollFinanceReportController
{
    public function __construct(
        DepartmentWisePayrollReportService $service,
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
        return 'reports.department-wise-payroll-report.view';
    }

    protected function reportKey(): string
    {
        return 'department-wise-payroll-report';
    }

    protected function viewDir(): string
    {
        return 'department_wise_payroll_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();

        return view('admin.reports.department_wise_payroll_report.index', compact('business', 'departments'));
    }

    protected function exportInstance($rows)
    {
        return new DepartmentWisePayrollReportExport($rows);
    }
}
