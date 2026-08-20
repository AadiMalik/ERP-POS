<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\TrialBalanceExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountTypeService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\TrialBalanceReportService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class TrialBalanceReportController extends Controller
{
    use ResponseAPI;

    protected $trial_balance_report_service;
    protected $business_service;
    protected $account_type_service;
    protected $document_send_log_service;
    protected $print_setting_resolver;

    public function __construct(
        TrialBalanceReportService $trial_balance_report_service,
        BusinessService $business_service,
        AccountTypeService $account_type_service,
        DocumentSendLogService $document_send_log_service,
        PrintSettingResolverService $print_setting_resolver
    ) {
        $this->middleware('permission:reports.trial-balance.view');
        $this->middleware('permission:reports.trial-balance.print')->only(['print']);
        $this->middleware('permission:reports.trial-balance.pdf')->only(['pdf']);
        $this->middleware('permission:reports.trial-balance.export')->only(['export']);
        $this->middleware('permission:reports.trial-balance.export-csv')->only(['exportCsv']);

        $this->trial_balance_report_service = $trial_balance_report_service;
        $this->business_service = $business_service;
        $this->account_type_service = $account_type_service;
        $this->document_send_log_service = $document_send_log_service;
        $this->print_setting_resolver = $print_setting_resolver;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $account_types = $this->account_type_service->getAll();

        return view('admin.reports.trial_balance.index', compact('business', 'account_types'));
    }

    public function data(Request $request)
    {
        return $this->trial_balance_report_service->getData($request->all());
    }

    public function print(Request $request)
    {
        $rows = $this->trial_balance_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'print');

        return view('admin.reports.trial_balance.print.print', compact('rows', 'request'));
    }

    public function pdf(Request $request)
    {
        $rows = $this->trial_balance_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView('admin.reports.trial_balance.pdf', compact('rows', 'request'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
            ->stream('trial-balance.pdf');
    }

    public function export(Request $request)
    {
        $rows = $this->trial_balance_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download(new TrialBalanceExport($rows), 'trial-balance.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->trial_balance_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download(new TrialBalanceExport($rows), 'trial-balance.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'trial_balance_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
