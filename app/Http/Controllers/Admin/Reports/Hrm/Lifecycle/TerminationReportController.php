<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Lifecycle;

use App\Enums\RoleNames;
use App\Exports\Hrm\TerminationReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Lifecycle\TerminationReportService;
use Illuminate\Http\Request;

class TerminationReportController extends BaseLifecycleReportController
{
    public function __construct(
        TerminationReportService $service,
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
        return 'reports.termination-report.view';
    }

    protected function reportKey(): string
    {
        return 'termination-report';
    }

    protected function viewDir(): string
    {
        return 'termination_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();

        return view('admin.reports.termination_report.index', compact('business', 'departments'));
    }

    protected function exportInstance($rows)
    {
        return new TerminationReportExport($rows);
    }
}
