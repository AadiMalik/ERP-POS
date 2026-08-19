<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Attendance;

use App\Enums\RoleNames;
use App\Exports\Hrm\LateAttendanceReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Attendance\LateAttendanceReportService;
use Illuminate\Http\Request;

class LateAttendanceReportController extends BaseAttendanceReportController
{
    public function __construct(
        LateAttendanceReportService $service,
        protected DepartmentService $department_service,
        protected EmployeeService $employee_service,
        protected BusinessService $business_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.late-attendance-report.view';
    }

    protected function reportKey(): string
    {
        return 'late-attendance-report';
    }

    protected function viewDir(): string
    {
        return 'late_attendance_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();
        $employees = $this->employee_service->getAllActive();

        return view('admin.reports.late_attendance_report.index', compact('business', 'departments', 'employees'));
    }

    protected function exportInstance($rows)
    {
        return new LateAttendanceReportExport($rows);
    }
}
