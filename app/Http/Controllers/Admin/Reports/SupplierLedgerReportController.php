<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\SupplierLedgerExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\Reports\SupplierLedgerReportService;
use App\Services\Concrete\Admin\SupplierService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class SupplierLedgerReportController extends Controller
{
    use ResponseAPI;

    protected $supplier_ledger_report_service;
    protected $business_service;
    protected $supplier_service;
    protected $document_send_log_service;

    public function __construct(
        SupplierLedgerReportService $supplier_ledger_report_service,
        BusinessService $business_service,
        SupplierService $supplier_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->supplier_ledger_report_service = $supplier_ledger_report_service;
        $this->business_service = $business_service;
        $this->supplier_service = $supplier_service;
        $this->document_send_log_service = $document_send_log_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();

        return view('admin.reports.supplier_ledger.index', compact('business', 'suppliers'));
    }

    public function data(Request $request)
    {
        try {
            return $this->supplier_ledger_report_service->getData($request->all());
        } catch (Exception $e) {
            return response()->json(['Message' => $e->getMessage()], 422);
        }
    }

    public function print(Request $request)
    {
        try {
            $result = $this->supplier_ledger_report_service->build($request->all());
        } catch (Exception $e) {
            abort(422, $e->getMessage());
        }

        if (!$result['supplier']) {
            abort(404);
        }

        $this->log($result['supplier']->business_id, $result['supplier']->supplier_id, 'print');

        return view('admin.reports.supplier_ledger.print.print', compact('result'));
    }

    public function pdf(Request $request)
    {
        try {
            $result = $this->supplier_ledger_report_service->build($request->all());
        } catch (Exception $e) {
            abort(422, $e->getMessage());
        }

        if (!$result['supplier']) {
            abort(404);
        }

        $this->log($result['supplier']->business_id, $result['supplier']->supplier_id, 'pdf');

        return Pdf::loadView('admin.reports.supplier_ledger.pdf', compact('result'))
            ->stream('supplier-ledger-' . $result['supplier']->code . '.pdf');
    }

    public function export(Request $request)
    {
        try {
            $result = $this->supplier_ledger_report_service->build($request->all());
        } catch (Exception $e) {
            abort(422, $e->getMessage());
        }

        if (!$result['supplier']) {
            abort(404);
        }

        $this->log($result['supplier']->business_id, $result['supplier']->supplier_id, 'export');

        return Excel::download(new SupplierLedgerExport($result), 'supplier-ledger-' . $result['supplier']->code . '.xlsx');
    }

    public function exportCsv(Request $request)
    {
        try {
            $result = $this->supplier_ledger_report_service->build($request->all());
        } catch (Exception $e) {
            abort(422, $e->getMessage());
        }

        if (!$result['supplier']) {
            abort(404);
        }

        $this->log($result['supplier']->business_id, $result['supplier']->supplier_id, 'export_csv');

        return Excel::download(new SupplierLedgerExport($result), 'supplier-ledger-' . $result['supplier']->code . '.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $record_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'supplier_ledger_report', $record_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
