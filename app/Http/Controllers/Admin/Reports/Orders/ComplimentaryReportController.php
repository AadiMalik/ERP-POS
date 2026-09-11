<?php

namespace App\Http\Controllers\Admin\Reports\Orders;

use App\Enums\RoleNames;
use App\Exports\Orders\ComplimentaryReportExport;
use App\Models\User;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ComplimentaryReasonService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Orders\ComplimentaryReportService;
use App\Services\Concrete\Admin\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplimentaryReportController extends BaseOrderReportController
{
    public function __construct(
        ComplimentaryReportService $service,
        protected BranchService $branch_service,
        protected WarehouseService $warehouse_service,
        protected CustomerService $customer_service,
        protected ComplimentaryReasonService $complimentary_reason_service,
        protected BusinessService $business_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.complimentary-report.view';
    }

    protected function reportKey(): string
    {
        return 'complimentary-report';
    }

    protected function viewDir(): string
    {
        return 'complimentary_report';
    }

    public function index(Request $request)
    {
        $is_superadmin = RoleNames::SUPERADMIN == getRoleName();
        $business_id = Auth::user()->business_id;

        $business = $is_superadmin ? $this->business_service->getAll() : collect();
        $branches = $is_superadmin ? collect() : $this->branch_service->getAllActive();
        $warehouses = $is_superadmin ? collect() : $this->warehouse_service->getAllActive();
        $customers = $this->customer_service->getAllActive($is_superadmin ? null : $business_id);
        $reasons = $is_superadmin ? collect() : $this->complimentary_reason_service->getActiveByBusiness($business_id);
        $users = $is_superadmin ? collect() : User::where('business_id', $business_id)->where('is_deleted', 0)->orderBy('name')->get();

        return view('admin.reports.complimentary_report.index', compact(
            'business',
            'branches',
            'warehouses',
            'customers',
            'reasons',
            'users'
        ));
    }

    protected function exportInstance($rows)
    {
        return new ComplimentaryReportExport($rows, Auth::user()->can('order.complimentary.view-cost'));
    }
}
