<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Leave;

use App\Enums\RoleNames;
use App\Exports\Hrm\DepartmentWiseLeaveReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\Hrm\LeaveTypeService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Leave\DepartmentWiseLeaveReportService;
use Illuminate\Http\Request;

class DepartmentWiseLeaveReportController extends BaseLeaveReportController
{
    public function __construct(
        DepartmentWiseLeaveReportService $service,
        protected DepartmentService $department_service,
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
        return 'reports.department-wise-leave-report.view';
    }

    protected function reportKey(): string
    {
        return 'department-wise-leave-report';
    }

    protected function viewDir(): string
    {
        return 'department_wise_leave_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();
        $leaveTypes = $this->leave_type_service->getAllActive();

        return view('admin.reports.department_wise_leave_report.index', compact('business', 'departments', 'leaveTypes'));
    }

    protected function exportInstance($rows)
    {
        return new DepartmentWiseLeaveReportExport($rows);
    }
}
