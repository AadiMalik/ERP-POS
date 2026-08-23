<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\SalesReportExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\SalesReportService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class SalesReportController extends Controller
{
    use ResponseAPI;

    protected $sales_report_service;
    protected $business_service;
    protected $warehouse_service;
    protected $document_send_log_service;
    protected $print_setting_resolver;

    public function __construct(
        SalesReportService $sales_report_service,
        BusinessService $business_service,
        WarehouseService $warehouse_service,
        DocumentSendLogService $document_send_log_service,
        PrintSettingResolverService $print_setting_resolver
    ) {
        $this->middleware('permission:reports.sales-report.view');
        $this->middleware('permission:reports.sales-report.print')->only(['print']);
        $this->middleware('permission:reports.sales-report.pdf')->only(['pdf']);
        $this->middleware('permission:reports.sales-report.export')->only(['export']);
        $this->middleware('permission:reports.sales-report.export-csv')->only(['exportCsv']);

        $this->sales_report_service = $sales_report_service;
        $this->business_service = $business_service;
        $this->warehouse_service = $warehouse_service;
        $this->document_send_log_service = $document_send_log_service;
        $this->print_setting_resolver = $print_setting_resolver;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $warehouses = $this->warehouse_service->getAllActive();

        return view('admin.reports.sales_report.index', compact('business', 'warehouses'));
    }

    public function data(Request $request)
    {
        return $this->sales_report_service->getData($request->all());
    }

    public function reconcile(Request $request)
    {
        return $this->success('Fetched', $this->sales_report_service->reconcile($request->all()));
    }

    public function print(Request $request)
    {
        $rows = $this->sales_report_service->build($request->all());
        $summary = $this->sales_report_service->reconcile($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'print');

        return view('admin.reports.sales_report.print.print', compact('rows', 'summary', 'request'));
    }

    public function pdf(Request $request)
    {
        $rows = $this->sales_report_service->build($request->all());
        $summary = $this->sales_report_service->reconcile($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView('admin.reports.sales_report.pdf', compact('rows', 'summary', 'request'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
            ->stream('sales-report.pdf');
    }

    public function export(Request $request)
    {
        $rows = $this->sales_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download(new SalesReportExport($rows), 'sales-report.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->sales_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download(new SalesReportExport($rows), 'sales-report.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'sales_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
