<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Leave;

use App\Enums\RoleNames;
use App\Exports\Hrm\EmployeeLeaveHistoryReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Services\Concrete\Admin\Hrm\LeaveTypeService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Leave\EmployeeLeaveHistoryReportService;
use Illuminate\Http\Request;

class EmployeeLeaveHistoryReportController extends BaseLeaveReportController
{
    public function __construct(
        EmployeeLeaveHistoryReportService $service,
        protected EmployeeService $employee_service,
        protected LeaveTypeService $leave_type_service,
        protected BusinessService $business_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.employee-leave-history-report.view';
    }

    protected function reportKey(): string
    {
        return 'employee-leave-history-report';
    }

    protected function viewDir(): string
    {
        return 'employee_leave_history_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $employees = $this->employee_service->getAllActive();
        $leaveTypes = $this->leave_type_service->getAllActive();

        return view('admin.reports.employee_leave_history_report.index', compact('business', 'employees', 'leaveTypes'));
    }

    protected function exportInstance($rows)
    {
        return new EmployeeLeaveHistoryReportExport($rows);
    }
}
