<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance;

use App\Enums\RoleNames;
use App\Exports\Hrm\SalaryComponentReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance\SalaryComponentReportService;
use Illuminate\Http\Request;

class SalaryComponentReportController extends BasePayrollFinanceReportController
{
    public function __construct(
        SalaryComponentReportService $service,
        protected BusinessService $business_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.salary-component-report.view';
    }

    protected function reportKey(): string
    {
        return 'salary-component-report';
    }

    protected function viewDir(): string
    {
        return 'salary_component_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;

        return view('admin.reports.salary_component_report.index', compact('business'));
    }

    protected function exportInstance($rows)
    {
        return new SalaryComponentReportExport($rows);
    }
}
