<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance;

use App\Enums\RoleNames;
use App\Exports\Hrm\EmployeeLedgerReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance\EmployeeLedgerReportService;
use Illuminate\Http\Request;

class EmployeeLedgerReportController extends BasePayrollFinanceReportController
{
    public function __construct(
        EmployeeLedgerReportService $service,
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
        return 'reports.employee-ledger-report.view';
    }

    protected function reportKey(): string
    {
        return 'employee-ledger-report';
    }

    protected function viewDir(): string
    {
        return 'employee_ledger_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $employees = $this->employee_service->getAllActive();

        return view('admin.reports.employee_ledger_report.index', compact('business', 'employees'));
    }

    protected function exportInstance($rows)
    {
        return new EmployeeLedgerReportExport($rows);
    }
}
