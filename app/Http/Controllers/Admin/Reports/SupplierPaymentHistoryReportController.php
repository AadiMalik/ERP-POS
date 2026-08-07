<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\SupplierPaymentHistoryExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Reports\SupplierPaymentHistoryReportService;
use App\Services\Concrete\Admin\SupplierService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class SupplierPaymentHistoryReportController extends Controller
{
    use ResponseAPI;

    protected $supplier_payment_history_report_service;
    protected $business_service;
    protected $supplier_service;
    protected $document_send_log_service;

    public function __construct(
        SupplierPaymentHistoryReportService $supplier_payment_history_report_service,
        BusinessService $business_service,
        SupplierService $supplier_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->supplier_payment_history_report_service = $supplier_payment_history_report_service;
        $this->business_service = $business_service;
        $this->supplier_service = $supplier_service;
        $this->document_send_log_service = $document_send_log_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();

        return view('admin.reports.supplier_payment_history.index', compact('business', 'suppliers'));
    }

    public function data(Request $request)
    {
        return $this->supplier_payment_history_report_service->getData($request->all());
    }

    public function print(Request $request)
    {
        $rows = $this->supplier_payment_history_report_service->getRows($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'print');

        return view('admin.reports.supplier_payment_history.print.print', compact('rows'));
    }

    public function pdf(Request $request)
    {
        $rows = $this->supplier_payment_history_report_service->getRows($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'pdf');

        return Pdf::loadView('admin.reports.supplier_payment_history.pdf', compact('rows'))
            ->setPaper('a4', 'landscape')
            ->stream('supplier-payment-history.pdf');
    }

    public function export(Request $request)
    {
        $query = $this->supplier_payment_history_report_service->exportQuery($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download(new SupplierPaymentHistoryExport($query), 'supplier-payment-history.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $query = $this->supplier_payment_history_report_service->exportQuery($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download(new SupplierPaymentHistoryExport($query), 'supplier-payment-history.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'supplier_payment_history_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
