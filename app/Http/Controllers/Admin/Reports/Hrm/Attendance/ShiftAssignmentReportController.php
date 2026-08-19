<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Attendance;

use App\Enums\RoleNames;
use App\Exports\Hrm\ShiftAssignmentReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\Hrm\ShiftService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Attendance\ShiftAssignmentReportService;
use Illuminate\Http\Request;

class ShiftAssignmentReportController extends BaseAttendanceReportController
{
    public function __construct(
        ShiftAssignmentReportService $service,
        protected DepartmentService $department_service,
        protected ShiftService $shift_service,
        protected BusinessService $business_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.shift-assignment-report.view';
    }

    protected function reportKey(): string
    {
        return 'shift-assignment-report';
    }

    protected function viewDir(): string
    {
        return 'shift_assignment_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();
        $shifts = $this->shift_service->getAllActive();

        return view('admin.reports.shift_assignment_report.index', compact('business', 'departments', 'shifts'));
    }

    protected function exportInstance($rows)
    {
        return new ShiftAssignmentReportExport($rows);
    }
}
