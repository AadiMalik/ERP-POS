<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Lifecycle;

use App\Enums\RoleNames;
use App\Exports\Hrm\EmployeeLifecycleReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Lifecycle\EmployeeLifecycleReportService;
use Illuminate\Http\Request;

class EmployeeLifecycleReportController extends BaseLifecycleReportController
{
    public function __construct(
        EmployeeLifecycleReportService $service,
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
        return 'reports.employee-lifecycle-report.view';
    }

    protected function reportKey(): string
    {
        return 'employee-lifecycle-report';
    }

    protected function viewDir(): string
    {
        return 'employee_lifecycle_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $employees = $this->employee_service->getAllActive();
        $rows = $this->service->build($request->all());
        $lifecycle = $rows->first();

        return view('admin.reports.employee_lifecycle_report.index', compact('business', 'employees', 'lifecycle'));
    }

    protected function exportInstance($rows)
    {
        return new EmployeeLifecycleReportExport($rows);
    }
}
