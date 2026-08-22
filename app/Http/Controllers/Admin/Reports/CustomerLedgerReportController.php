<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\CustomerLedgerExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\CustomerLedgerReportService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class CustomerLedgerReportController extends Controller
{
    use ResponseAPI;

    protected $customer_ledger_report_service;
    protected $business_service;
    protected $customer_service;
    protected $document_send_log_service;
    protected $print_setting_resolver;

    public function __construct(
        CustomerLedgerReportService $customer_ledger_report_service,
        BusinessService $business_service,
        CustomerService $customer_service,
        DocumentSendLogService $document_send_log_service,
        PrintSettingResolverService $print_setting_resolver
    ) {
        $this->middleware('permission:reports.customer-ledger.view');
        $this->middleware('permission:reports.customer-ledger.print')->only(['print']);
        $this->middleware('permission:reports.customer-ledger.pdf')->only(['pdf']);
        $this->middleware('permission:reports.customer-ledger.export')->only(['export']);
        $this->middleware('permission:reports.customer-ledger.export-csv')->only(['exportCsv']);

        $this->customer_ledger_report_service = $customer_ledger_report_service;
        $this->business_service = $business_service;
        $this->customer_service = $customer_service;
        $this->document_send_log_service = $document_send_log_service;
        $this->print_setting_resolver = $print_setting_resolver;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $customers = $this->customer_service->getAllActive();

        return view('admin.reports.customer_ledger.index', compact('business', 'customers'));
    }

    public function data(Request $request)
    {
        try {
            return $this->customer_ledger_report_service->getData($request->all());
        } catch (Exception $e) {
            return response()->json(['Message' => $e->getMessage()], 422);
        }
    }

    public function print(Request $request)
    {
        try {
            $result = $this->customer_ledger_report_service->build($request->all());
        } catch (Exception $e) {
            abort(422, $e->getMessage());
        }

        if (!$result['customer']) {
            abort(404);
        }

        $this->log($result['customer']->business_id, $result['customer']->user_id, 'print');

        return view('admin.reports.customer_ledger.print.print', compact('result'));
    }

    public function pdf(Request $request)
    {
        try {
            $result = $this->customer_ledger_report_service->build($request->all());
        } catch (Exception $e) {
            abort(422, $e->getMessage());
        }

        if (!$result['customer']) {
            abort(404);
        }

        $this->log($result['customer']->business_id, $result['customer']->user_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($result['customer']->business_id);

        return Pdf::loadView('admin.reports.customer_ledger.pdf', compact('result'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
            ->stream('customer-ledger-' . $result['customer']->code . '.pdf');
    }

    public function export(Request $request)
    {
        try {
            $result = $this->customer_ledger_report_service->build($request->all());
        } catch (Exception $e) {
            abort(422, $e->getMessage());
        }

        if (!$result['customer']) {
            abort(404);
        }

        $this->log($result['customer']->business_id, $result['customer']->user_id, 'export');

        return Excel::download(new CustomerLedgerExport($result), 'customer-ledger-' . $result['customer']->code . '.xlsx');
    }

    public function exportCsv(Request $request)
    {
        try {
            $result = $this->customer_ledger_report_service->build($request->all());
        } catch (Exception $e) {
            abort(422, $e->getMessage());
        }

        if (!$result['customer']) {
            abort(404);
        }

        $this->log($result['customer']->business_id, $result['customer']->user_id, 'export_csv');

        return Excel::download(new CustomerLedgerExport($result), 'customer-ledger-' . $result['customer']->code . '.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $record_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'customer_ledger_report', $record_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
