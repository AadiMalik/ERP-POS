<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Leave;

use App\Enums\RoleNames;
use App\Exports\Hrm\LeaveTypeWiseReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\LeaveTypeService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Leave\LeaveTypeWiseReportService;
use Illuminate\Http\Request;

class LeaveTypeWiseReportController extends BaseLeaveReportController
{
    public function __construct(
        LeaveTypeWiseReportService $service,
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
        return 'reports.leave-type-wise-report.view';
    }

    protected function reportKey(): string
    {
        return 'leave-type-wise-report';
    }

    protected function viewDir(): string
    {
        return 'leave_type_wise_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $leaveTypes = $this->leave_type_service->getAllActive();

        return view('admin.reports.leave_type_wise_report.index', compact('business', 'leaveTypes'));
    }

    protected function exportInstance($rows)
    {
        return new LeaveTypeWiseReportExport($rows);
    }
}
