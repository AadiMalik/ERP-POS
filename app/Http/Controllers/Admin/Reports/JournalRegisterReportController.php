<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\JournalRegisterExport;
use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\JournalService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\JournalRegisterReportService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class JournalRegisterReportController extends Controller
{
    use ResponseAPI;

    protected $journal_register_report_service;
    protected $business_service;
    protected $journal_service;
    protected $document_send_log_service;
    protected $print_setting_resolver;

    public function __construct(
        JournalRegisterReportService $journal_register_report_service,
        BusinessService $business_service,
        JournalService $journal_service,
        DocumentSendLogService $document_send_log_service,
        PrintSettingResolverService $print_setting_resolver
    ) {
        $this->middleware('permission:reports.journal-register.view');
        $this->middleware('permission:reports.journal-register.print')->only(['print']);
        $this->middleware('permission:reports.journal-register.pdf')->only(['pdf']);
        $this->middleware('permission:reports.journal-register.export')->only(['export']);
        $this->middleware('permission:reports.journal-register.export-csv')->only(['exportCsv']);

        $this->journal_register_report_service = $journal_register_report_service;
        $this->business_service = $business_service;
        $this->journal_service = $journal_service;
        $this->document_send_log_service = $document_send_log_service;
        $this->print_setting_resolver = $print_setting_resolver;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $journals = $this->journal_service->getAll();
        $source_types = JournalEntry::where('business_id', Auth::user()->business_id)
            ->whereNotNull('source_type')
            ->distinct()
            ->orderBy('source_type')
            ->pluck('source_type');

        return view('admin.reports.journal_register.index', compact('business', 'journals', 'source_types'));
    }

    public function data(Request $request)
    {
        return $this->journal_register_report_service->getData($request->all());
    }

    public function print(Request $request)
    {
        $rows = $this->journal_register_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'print');

        return view('admin.reports.journal_register.print.print', compact('rows', 'request'));
    }

    public function pdf(Request $request)
    {
        $rows = $this->journal_register_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView('admin.reports.journal_register.pdf', compact('rows', 'request'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
            ->stream('journal-register.pdf');
    }

    public function export(Request $request)
    {
        $rows = $this->journal_register_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download(new JournalRegisterExport($rows), 'journal-register.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->journal_register_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download(new JournalRegisterExport($rows), 'journal-register.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'journal_register_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
