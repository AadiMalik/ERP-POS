<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\TaxReportExport;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountingSetting;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\Accounting\AccountClassifier;
use App\Services\Concrete\Admin\Reports\TaxReportService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class TaxReportController extends Controller
{
    use ResponseAPI;

    protected $tax_report_service;
    protected $business_service;
    protected $classifier;
    protected $document_send_log_service;
    protected $print_setting_resolver;

    public function __construct(
        TaxReportService $tax_report_service,
        BusinessService $business_service,
        AccountClassifier $classifier,
        DocumentSendLogService $document_send_log_service,
        PrintSettingResolverService $print_setting_resolver
    ) {
        $this->middleware('permission:reports.tax-report.view');
        $this->middleware('permission:reports.tax-report.print')->only(['print']);
        $this->middleware('permission:reports.tax-report.pdf')->only(['pdf']);
        $this->middleware('permission:reports.tax-report.export')->only(['export']);
        $this->middleware('permission:reports.tax-report.export-csv')->only(['exportCsv']);

        $this->tax_report_service = $tax_report_service;
        $this->business_service = $business_service;
        $this->classifier = $classifier;
        $this->document_send_log_service = $document_send_log_service;
        $this->print_setting_resolver = $print_setting_resolver;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $settings = AccountingSetting::where('business_id', Auth::user()->business_id)->first();
        $accounts = Account::with(['accountType', 'accountSubType'])
            ->where('business_id', Auth::user()->business_id)
            ->where('is_deleted', 0)
            ->where('status', 'active')
            ->orderBy('code')
            ->get()
            ->filter(fn ($account) => $this->classifier->isTaxAccount($account, $settings))
            ->values();

        return view('admin.reports.tax_report.index', compact('business', 'accounts'));
    }

    public function data(Request $request)
    {
        return $this->tax_report_service->getData($request->all());
    }

    public function print(Request $request)
    {
        $rows = $this->tax_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'print');

        return view('admin.reports.tax_report.print.print', compact('rows', 'request'));
    }

    public function pdf(Request $request)
    {
        $rows = $this->tax_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView('admin.reports.tax_report.pdf', compact('rows', 'request'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
            ->stream('tax-report.pdf');
    }

    public function export(Request $request)
    {
        $rows = $this->tax_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download(new TaxReportExport($rows), 'tax-report.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->tax_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download(new TaxReportExport($rows), 'tax-report.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'tax_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
