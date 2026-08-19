<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance;

use App\Enums\RoleNames;
use App\Exports\Hrm\PayrollDisbursementReportExport;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance\PayrollDisbursementReportService;
use Illuminate\Http\Request;

class PayrollDisbursementReportController extends BasePayrollFinanceReportController
{
    public function __construct(
        PayrollDisbursementReportService $service,
        protected BusinessService $business_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.payroll-disbursement-report.view';
    }

    protected function reportKey(): string
    {
        return 'payroll-disbursement-report';
    }

    protected function viewDir(): string
    {
        return 'payroll_disbursement_report';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;

        return view('admin.reports.payroll_disbursement_report.index', compact('business'));
    }

    protected function exportInstance($rows)
    {
        return new PayrollDisbursementReportExport($rows);
    }
}
