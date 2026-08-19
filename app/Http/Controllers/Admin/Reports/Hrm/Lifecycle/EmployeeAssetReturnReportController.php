<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Lifecycle;

use App\Enums\RoleNames;
use App\Exports\Hrm\EmployeeAssetReturnReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\Lifecycle\EmployeeAssetReturnReportService;
use Illuminate\Http\Request;

class EmployeeAssetReturnReportController extends BaseLifecycleReportController
{
    public function __construct(
        EmployeeAssetReturnReportService $service,
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
        return 'reports.employee-asset-return-report.view';
    }

    protected function reportKey(): string
    {
        return 'employee-asset-return-report';
    }

    protected function viewDir(): string
    {
        return 'employee_asset_return_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $employees = $this->employee_service->getAllActive();

        return view('admin.reports.employee_asset_return_report.index', compact('business', 'employees'));
    }

    protected function exportInstance($rows)
    {
        return new EmployeeAssetReturnReportExport($rows);
    }
}
