<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Employee;

use App\Enums\RoleNames;
use App\Exports\Hrm\EmployeeJoiningReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\Hrm\DesignationService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Employee\EmployeeJoiningReportService;

class EmployeeJoiningReportController extends BaseEmployeeReportController
{
    public function __construct(
        EmployeeJoiningReportService $service,
        protected DepartmentService $department_service,
        protected DesignationService $designation_service,
        protected BusinessService $business_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.employee-joining-report.view';
    }

    protected function reportKey(): string
    {
        return 'employee-joining-report';
    }

    protected function viewDir(): string
    {
        return 'employee_joining_report';
    }

    public function index()
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();
        $designations = $this->designation_service->getAllActive();

        return view('admin.reports.employee_joining_report.index', compact('business', 'departments', 'designations'));
    }

    protected function exportInstance($rows)
    {
        return new EmployeeJoiningReportExport($rows);
    }
}
