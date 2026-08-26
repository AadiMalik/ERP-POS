<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\Reports\VoucherUsageReportService;
use App\Services\Concrete\Admin\VoucherService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherUsageReportController extends Controller
{
    use ResponseAPI;

    protected $report_service;
    protected $business_service;
    protected $voucher_service;

    public function __construct(
        VoucherUsageReportService $report_service,
        BusinessService $business_service,
        VoucherService $voucher_service
    ) {
        $this->middleware('permission:reports.voucher-usage.view');
        $this->middleware('permission:reports.voucher-usage.export')->only(['export']);

        $this->report_service = $report_service;
        $this->business_service = $business_service;
        $this->voucher_service = $voucher_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $business_id = Auth::user()->business_id;
        $vouchers = $business_id
            ? $this->voucher_service->listActiveByBusiness($business_id)
            : collect();

        return view('admin.reports.voucher_usage_report.index', compact('business', 'vouchers'));
    }

    public function data(Request $request)
    {
        return $this->report_service->getData($request->all());
    }

    public function summary(Request $request)
    {
        return $this->success('Fetched', $this->report_service->summary($request->all()));
    }
}
