<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\BalanceSheetExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\BalanceSheetReportService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class BalanceSheetReportController extends Controller
{
    use ResponseAPI;

    protected $balance_sheet_report_service;
    protected $business_service;
    protected $document_send_log_service;
    protected $print_setting_resolver;

    public function __construct(
        BalanceSheetReportService $balance_sheet_report_service,
        BusinessService $business_service,
        DocumentSendLogService $document_send_log_service,
        PrintSettingResolverService $print_setting_resolver
    ) {
        $this->balance_sheet_report_service = $balance_sheet_report_service;
        $this->business_service = $business_service;
        $this->document_send_log_service = $document_send_log_service;
        $this->print_setting_resolver = $print_setting_resolver;
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $result = $this->balance_sheet_report_service->build($request->all());

        return view('admin.reports.balance_sheet.index', compact('business', 'result'));
    }

    public function print(Request $request)
    {
        $result = $this->balance_sheet_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'print');

        return view('admin.reports.balance_sheet.print.print', compact('result', 'request'));
    }

    public function pdf(Request $request)
    {
        $result = $this->balance_sheet_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView('admin.reports.balance_sheet.pdf', compact('result', 'request'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
            ->stream('balance-sheet.pdf');
    }

    public function export(Request $request)
    {
        $result = $this->balance_sheet_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download(new BalanceSheetExport($result), 'balance-sheet.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $result = $this->balance_sheet_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download(new BalanceSheetExport($result), 'balance-sheet.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'balance_sheet_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
