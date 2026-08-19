<?php

namespace App\Http\Controllers\Admin\Reports\Hrm;

use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\Reports\Hrm\HrDashboardReportService;
use Illuminate\Http\Request;

class HrDashboardReportController extends Controller
{
    public function __construct(
        protected HrDashboardReportService $service,
        protected BusinessService $business_service
    ) {
        $this->middleware('permission:reports.hr-dashboard-report.view');
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $stats = $this->service->build($request->all());

        return view('admin.reports.hr_dashboard_report.index', compact('business', 'stats'));
    }
}
