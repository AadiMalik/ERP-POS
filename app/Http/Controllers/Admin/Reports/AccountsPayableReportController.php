<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\AccountsPayableExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\AccountsPayableReportService;
use App\Services\Concrete\Admin\SupplierService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class AccountsPayableReportController extends Controller
{
    use ResponseAPI;

    protected $accounts_payable_report_service;
    protected $business_service;
    protected $supplier_service;
    protected $document_send_log_service;
    protected $print_setting_resolver;

    public function __construct(
        AccountsPayableReportService $accounts_payable_report_service,
        BusinessService $business_service,
        SupplierService $supplier_service,
        DocumentSendLogService $document_send_log_service,
        PrintSettingResolverService $print_setting_resolver
    ) {
        $this->middleware('permission:reports.accounts-payable.view');
        $this->middleware('permission:reports.accounts-payable.print')->only(['print']);
        $this->middleware('permission:reports.accounts-payable.pdf')->only(['pdf']);
        $this->middleware('permission:reports.accounts-payable.export')->only(['export']);
        $this->middleware('permission:reports.accounts-payable.export-csv')->only(['exportCsv']);

        $this->accounts_payable_report_service = $accounts_payable_report_service;
        $this->business_service = $business_service;
        $this->supplier_service = $supplier_service;
        $this->document_send_log_service = $document_send_log_service;
        $this->print_setting_resolver = $print_setting_resolver;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();
        $statuses = AccountsPayableReportService::STATUS_LABELS;

        return view('admin.reports.accounts_payable.index', compact('business', 'suppliers', 'statuses'));
    }

    public function data(Request $request)
    {
        return $this->accounts_payable_report_service->getData($request->all());
    }

    public function print(Request $request)
    {
        $invoices = $this->accounts_payable_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'print');

        return view('admin.reports.accounts_payable.print.print', compact('invoices', 'request'));
    }

    public function pdf(Request $request)
    {
        $invoices = $this->accounts_payable_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView('admin.reports.accounts_payable.pdf', compact('invoices', 'request'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
            ->stream('accounts-payable.pdf');
    }

    public function export(Request $request)
    {
        $invoices = $this->accounts_payable_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download(new AccountsPayableExport($invoices), 'accounts-payable.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $invoices = $this->accounts_payable_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download(new AccountsPayableExport($invoices), 'accounts-payable.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'accounts_payable_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
