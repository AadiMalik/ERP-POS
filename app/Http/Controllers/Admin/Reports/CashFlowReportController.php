<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\CashFlowExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\CashFlowReportService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class CashFlowReportController extends Controller
{
    use ResponseAPI;

    protected $cash_flow_report_service;
    protected $business_service;
    protected $document_send_log_service;
    protected $print_setting_resolver;

    public function __construct(
        CashFlowReportService $cash_flow_report_service,
        BusinessService $business_service,
        DocumentSendLogService $document_send_log_service,
        PrintSettingResolverService $print_setting_resolver
    ) {
        $this->middleware('permission:reports.cash-flow.view');
        $this->middleware('permission:reports.cash-flow.print')->only(['print']);
        $this->middleware('permission:reports.cash-flow.pdf')->only(['pdf']);
        $this->middleware('permission:reports.cash-flow.export')->only(['export']);
        $this->middleware('permission:reports.cash-flow.export-csv')->only(['exportCsv']);

        $this->cash_flow_report_service = $cash_flow_report_service;
        $this->business_service = $business_service;
        $this->document_send_log_service = $document_send_log_service;
        $this->print_setting_resolver = $print_setting_resolver;
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $result = $this->cash_flow_report_service->build($request->all());

        return view('admin.reports.cash_flow.index', compact('business', 'result'));
    }

    public function print(Request $request)
    {
        $result = $this->cash_flow_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'print');

        return view('admin.reports.cash_flow.print.print', compact('result', 'request'));
    }

    public function pdf(Request $request)
    {
        $result = $this->cash_flow_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView('admin.reports.cash_flow.pdf', compact('result', 'request'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
            ->stream('cash-flow.pdf');
    }

    public function export(Request $request)
    {
        $result = $this->cash_flow_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download(new CashFlowExport($result), 'cash-flow.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $result = $this->cash_flow_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download(new CashFlowExport($result), 'cash-flow.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'cash_flow_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
