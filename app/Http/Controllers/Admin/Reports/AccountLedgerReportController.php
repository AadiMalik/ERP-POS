<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\AccountLedgerExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\AccountLedgerReportService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class AccountLedgerReportController extends Controller
{
    use ResponseAPI;

    protected $account_ledger_report_service;
    protected $business_service;
    protected $account_service;
    protected $document_send_log_service;
    protected $print_setting_resolver;

    public function __construct(
        AccountLedgerReportService $account_ledger_report_service,
        BusinessService $business_service,
        AccountService $account_service,
        DocumentSendLogService $document_send_log_service,
        PrintSettingResolverService $print_setting_resolver
    ) {
        $this->middleware('permission:reports.account-ledger.view');

        $this->account_ledger_report_service = $account_ledger_report_service;
        $this->business_service = $business_service;
        $this->account_service = $account_service;
        $this->document_send_log_service = $document_send_log_service;
        $this->print_setting_resolver = $print_setting_resolver;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $accounts = $this->account_service->getAllActive(Auth::user()->business_id);

        return view('admin.reports.account_ledger.index', compact('business', 'accounts'));
    }

    public function data(Request $request)
    {
        return $this->account_ledger_report_service->getData($request->all());
    }

    public function print(Request $request)
    {
        try {
            $result = $this->account_ledger_report_service->build($request->all());
            $business_id = $request->business_id ?? Auth::user()->business_id;

            $this->log($business_id, 'print');

            return view('admin.reports.account_ledger.print.print', compact('result', 'request'));
        } catch (Exception $e) {
            abort(422, $e->getMessage());
        }
    }

    public function pdf(Request $request)
    {
        try {
            $result = $this->account_ledger_report_service->build($request->all());
            $business_id = $request->business_id ?? Auth::user()->business_id;

            $this->log($business_id, 'pdf');

            $print_config = $this->print_setting_resolver->resolve($business_id);

            return Pdf::loadView('admin.reports.account_ledger.pdf', compact('result', 'request'))
                ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
                ->stream('account-ledger.pdf');
        } catch (Exception $e) {
            abort(422, $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $result = $this->account_ledger_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download(new AccountLedgerExport($result), 'account-ledger.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $result = $this->account_ledger_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download(new AccountLedgerExport($result), 'account-ledger.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'account_ledger_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
