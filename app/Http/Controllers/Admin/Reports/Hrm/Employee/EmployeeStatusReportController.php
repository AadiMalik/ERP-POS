<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Employee;

use App\Enums\RoleNames;
use App\Exports\Hrm\EmployeeStatusReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Employee\EmployeeStatusReportService;

class EmployeeStatusReportController extends BaseEmployeeReportController
{
    public function __construct(
        EmployeeStatusReportService $service,
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
        return 'reports.employee-status-report.view';
    }

    protected function reportKey(): string
    {
        return 'employee-status-report';
    }

    protected function viewDir(): string
    {
        return 'employee_status_report';
    }

    public function index()
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();

        return view('admin.reports.employee_status_report.index', compact('business', 'departments'));
    }

    protected function exportInstance($rows)
    {
        return new EmployeeStatusReportExport($rows);
    }
}
