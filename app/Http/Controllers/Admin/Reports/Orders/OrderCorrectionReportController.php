<?php

namespace App\Http\Controllers\Admin\Reports\Orders;

use App\Enums\RoleNames;
use App\Exports\Orders\OrderCorrectionReportExport;
use App\Models\User;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Orders\OrderCorrectionReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderCorrectionReportController extends BaseOrderReportController
{
    public function __construct(
        OrderCorrectionReportService $service,
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
        return 'reports.order-correction-report.view';
    }

    protected function reportKey(): string
    {
        return 'order-correction-report';
    }

    protected function viewDir(): string
    {
        return 'order_correction';
    }

    public function index(Request $request)
    {
        $is_superadmin = RoleNames::SUPERADMIN == getRoleName();
        $business_id = Auth::user()->business_id;

        $business = $is_superadmin ? $this->business_service->getAll() : collect();
        $branches = $is_superadmin ? collect() : $this->branch_service->getAllActive();
        $managers = $is_superadmin ? collect() : User::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();

        return view('admin.reports.order_correction.index', compact('business', 'branches', 'managers'));
    }

    protected function exportInstance($rows)
    {
        return new OrderCorrectionReportExport($rows);
    }
}
