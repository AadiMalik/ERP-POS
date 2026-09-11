<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\BusinessSummaryExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\BusinessSummaryReportService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class BusinessSummaryReportController extends Controller
{
    use ResponseAPI;

    public function __construct(
        protected BusinessSummaryReportService $business_summary_report_service,
        protected BusinessService $business_service,
        protected BranchService $branch_service,
        protected DocumentSendLogService $document_send_log_service,
        protected PrintSettingResolverService $print_setting_resolver
    ) {
        $this->middleware('permission:reports.business-summary.view');
        $this->middleware('permission:reports.business-summary.print')->only(['print']);
        $this->middleware('permission:reports.business-summary.pdf')->only(['pdf']);
        $this->middleware('permission:reports.business-summary.export')->only(['export']);
        $this->middleware('permission:reports.business-summary.export-csv')->only(['exportCsv']);
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        $result = $this->business_summary_report_service->build($request->all());

        return view('admin.reports.business_summary.index', compact('business', 'branches', 'result'));
    }

    public function data(Request $request)
    {
        $result = $this->business_summary_report_service->build($request->all());

        return $this->success(__('reports.business_summary.refreshed'), $result);
    }

    public function print(Request $request)
    {
        $result = $this->business_summary_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;
        $this->log($business_id, 'print');

        return view('admin.reports.business_summary.print.print', compact('result', 'request'));
    }

    public function pdf(Request $request)
    {
        $result = $this->business_summary_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;
        $this->log($business_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView('admin.reports.business_summary.pdf', compact('result', 'request'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
            ->stream('business-summary.pdf');
    }

    public function export(Request $request)
    {
        $result = $this->business_summary_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;
        $this->log($business_id, 'export');

        return Excel::download(new BusinessSummaryExport($result), 'business-summary.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $result = $this->business_summary_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;
        $this->log($business_id, 'export_csv');

        return Excel::download(new BusinessSummaryExport($result), 'business-summary.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'business_summary_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
