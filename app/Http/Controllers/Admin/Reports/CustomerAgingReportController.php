<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\CustomerAgingExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\CustomerAgingReportService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class CustomerAgingReportController extends Controller
{
    use ResponseAPI;

    protected $customer_aging_report_service;
    protected $business_service;
    protected $customer_service;
    protected $document_send_log_service;
    protected $print_setting_resolver;

    public function __construct(
        CustomerAgingReportService $customer_aging_report_service,
        BusinessService $business_service,
        CustomerService $customer_service,
        DocumentSendLogService $document_send_log_service,
        PrintSettingResolverService $print_setting_resolver
    ) {
        $this->middleware('permission:reports.customer-aging.view');
        $this->middleware('permission:reports.customer-aging.print')->only(['print']);
        $this->middleware('permission:reports.customer-aging.pdf')->only(['pdf']);
        $this->middleware('permission:reports.customer-aging.export')->only(['export']);
        $this->middleware('permission:reports.customer-aging.export-csv')->only(['exportCsv']);

        $this->customer_aging_report_service = $customer_aging_report_service;
        $this->business_service = $business_service;
        $this->customer_service = $customer_service;
        $this->document_send_log_service = $document_send_log_service;
        $this->print_setting_resolver = $print_setting_resolver;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $customers = $this->customer_service->getAllActive();

        return view('admin.reports.customer_aging.index', compact('business', 'customers'));
    }

    public function data(Request $request)
    {
        return $this->customer_aging_report_service->getData($request->all());
    }

    public function print(Request $request)
    {
        $rows = $this->customer_aging_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'print');

        return view('admin.reports.customer_aging.print.print', compact('rows'));
    }

    public function pdf(Request $request)
    {
        $rows = $this->customer_aging_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView('admin.reports.customer_aging.pdf', compact('rows'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'landscape'))
            ->stream('customer-aging.pdf');
    }

    public function export(Request $request)
    {
        $rows = $this->customer_aging_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download(new CustomerAgingExport($rows), 'customer-aging.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->customer_aging_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download(new CustomerAgingExport($rows), 'customer-aging.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'customer_aging_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
