<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Attendance;

use App\Enums\RoleNames;
use App\Exports\Hrm\AttendanceRegisterReportExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Attendance\AttendanceRegisterReportService;
use Illuminate\Http\Request;

class AttendanceRegisterReportController extends BaseAttendanceReportController
{
    public function __construct(
        AttendanceRegisterReportService $service,
        protected DepartmentService $department_service,
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
        return 'reports.attendance-register.view';
    }

    protected function reportKey(): string
    {
        return 'attendance-register';
    }

    protected function viewDir(): string
    {
        return 'attendance_register';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();
        $branches = $this->branch_service->getAllActive();

        $rows = $this->service->build($request->all());
        $dates = $this->service->dateRange($request->all());

        return view('admin.reports.attendance_register.index', compact('business', 'departments', 'branches', 'rows', 'dates'));
    }

    protected function exportInstance($rows)
    {
        return new AttendanceRegisterReportExport($rows);
    }
}
