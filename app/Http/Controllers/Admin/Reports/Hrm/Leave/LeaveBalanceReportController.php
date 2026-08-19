<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Leave;

use App\Enums\RoleNames;
use App\Exports\Hrm\LeaveBalanceReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Services\Concrete\Admin\Hrm\LeaveTypeService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Leave\LeaveBalanceReportService;
use Illuminate\Http\Request;

class LeaveBalanceReportController extends BaseLeaveReportController
{
    public function __construct(
        LeaveBalanceReportService $service,
        protected DepartmentService $department_service,
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
        return 'reports.leave-balance-report.view';
    }

    protected function reportKey(): string
    {
        return 'leave-balance-report';
    }

    protected function viewDir(): string
    {
        return 'leave_balance_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();
        $employees = $this->employee_service->getAllActive();
        $leaveTypes = $this->leave_type_service->getAllActive();

        return view('admin.reports.leave_balance_report.index', compact('business', 'departments', 'employees', 'leaveTypes'));
    }

    protected function exportInstance($rows)
    {
        return new LeaveBalanceReportExport($rows);
    }
}
