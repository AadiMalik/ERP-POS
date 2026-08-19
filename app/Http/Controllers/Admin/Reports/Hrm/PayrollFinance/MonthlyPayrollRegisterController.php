<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance;

use App\Enums\RoleNames;
use App\Exports\Hrm\MonthlyPayrollRegisterExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance\MonthlyPayrollRegisterService;
use Illuminate\Http\Request;

class MonthlyPayrollRegisterController extends BasePayrollFinanceReportController
{
    public function __construct(
        MonthlyPayrollRegisterService $service,
        protected DepartmentService $department_service,
        protected BranchService $branch_service,
        protected BusinessService $business_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.monthly-payroll-register.view';
    }

    protected function reportKey(): string
    {
        return 'monthly-payroll-register';
    }

    protected function viewDir(): string
    {
        return 'monthly_payroll_register';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $departments = $this->department_service->getAllActive();
        $branches = $this->branch_service->getAllActive();

        return view('admin.reports.monthly_payroll_register.index', compact('business', 'departments', 'branches'));
    }

    protected function exportInstance($rows)
    {
        return new MonthlyPayrollRegisterExport($rows);
    }
}
