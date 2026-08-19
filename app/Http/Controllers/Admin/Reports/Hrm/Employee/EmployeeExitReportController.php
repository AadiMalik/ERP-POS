<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Employee;

use App\Enums\RoleNames;
use App\Exports\Hrm\EmployeeExitReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Employee\EmployeeExitReportService;

class EmployeeExitReportController extends BaseEmployeeReportController
{
    public function __construct(
        EmployeeExitReportService $service,
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
        return 'reports.employee-exit-report.view';
    }

    protected function reportKey(): string
    {
        return 'employee-exit-report';
    }

    protected function viewDir(): string
    {
        return 'employee_exit_report';
    }

    public function index()
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();

        return view('admin.reports.employee_exit_report.index', compact('business', 'departments'));
    }

    protected function exportInstance($rows)
    {
        return new EmployeeExitReportExport($rows);
    }
}
