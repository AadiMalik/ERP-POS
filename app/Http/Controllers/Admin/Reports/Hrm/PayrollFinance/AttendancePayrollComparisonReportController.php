<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance;

use App\Enums\RoleNames;
use App\Exports\Hrm\AttendancePayrollComparisonReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance\AttendancePayrollComparisonReportService;
use Illuminate\Http\Request;

class AttendancePayrollComparisonReportController extends BasePayrollFinanceReportController
{
    public function __construct(
        AttendancePayrollComparisonReportService $service,
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
        return 'reports.attendance-payroll-comparison-report.view';
    }

    protected function reportKey(): string
    {
        return 'attendance-payroll-comparison-report';
    }

    protected function viewDir(): string
    {
        return 'attendance_payroll_comparison_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();

        return view('admin.reports.attendance_payroll_comparison_report.index', compact('business', 'departments'));
    }

    protected function exportInstance($rows)
    {
        return new AttendancePayrollComparisonReportExport($rows);
    }
}
