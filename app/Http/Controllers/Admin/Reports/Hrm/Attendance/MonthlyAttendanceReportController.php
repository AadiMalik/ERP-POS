<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Attendance;

use App\Enums\RoleNames;
use App\Exports\Hrm\MonthlyAttendanceReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Attendance\MonthlyAttendanceReportService;
use Illuminate\Http\Request;

class MonthlyAttendanceReportController extends BaseAttendanceReportController
{
    public function __construct(
        MonthlyAttendanceReportService $service,
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
        return 'reports.monthly-attendance-report.view';
    }

    protected function reportKey(): string
    {
        return 'monthly-attendance-report';
    }

    protected function viewDir(): string
    {
        return 'monthly_attendance_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();

        $month = (int) ($request->month ?? now()->month);
        $year = (int) ($request->year ?? now()->year);
        $rows = $this->service->build($request->all());
        $daysInMonth = $this->service->daysInMonth($request->all());

        return view('admin.reports.monthly_attendance_report.index', compact('business', 'departments', 'rows', 'daysInMonth', 'month', 'year'));
    }

    protected function exportInstance($rows)
    {
        return new MonthlyAttendanceReportExport($rows);
    }
}
