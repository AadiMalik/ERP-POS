<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Employee;

use App\Enums\RoleNames;
use App\Exports\Hrm\DepartmentWiseEmployeeReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Employee\DepartmentWiseEmployeeReportService;

class DepartmentWiseEmployeeReportController extends BaseEmployeeReportController
{
    public function __construct(
        DepartmentWiseEmployeeReportService $service,
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
        return 'reports.department-wise-employee-report.view';
    }

    protected function reportKey(): string
    {
        return 'department-wise-employee-report';
    }

    protected function viewDir(): string
    {
        return 'department_wise_employee_report';
    }

    public function index()
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();

        return view('admin.reports.department_wise_employee_report.index', compact('business', 'departments'));
    }

    protected function exportInstance($rows)
    {
        return new DepartmentWiseEmployeeReportExport($rows);
    }
}
