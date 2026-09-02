<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\FixedAssetRegisterExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\FixedAssetCategoryService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\FixedAssetRegisterReportService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class FixedAssetRegisterReportController extends Controller
{
    use ResponseAPI;

    protected $report_service;
    protected $business_service;
    protected $branch_service;
    protected $category_service;
    protected $document_send_log_service;
    protected $print_setting_resolver;

    public function __construct(
        FixedAssetRegisterReportService $report_service,
        BusinessService $business_service,
        BranchService $branch_service,
        FixedAssetCategoryService $category_service,
        DocumentSendLogService $document_send_log_service,
        PrintSettingResolverService $print_setting_resolver
    ) {
        $this->middleware('permission:reports.fixed-asset-register.view');
        $this->middleware('permission:reports.fixed-asset-register.print')->only(['print']);
        $this->middleware('permission:reports.fixed-asset-register.pdf')->only(['pdf']);
        $this->middleware('permission:reports.fixed-asset-register.export')->only(['export']);
        $this->middleware('permission:reports.fixed-asset-register.export-csv')->only(['exportCsv']);

        $this->report_service = $report_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->category_service = $category_service;
        $this->document_send_log_service = $document_send_log_service;
        $this->print_setting_resolver = $print_setting_resolver;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAll();
        $categories = $this->category_service->getAllActive(Auth::user()->business_id);

        return view('admin.reports.fixed_asset_register.index', compact('business', 'branches', 'categories'));
    }

    public function data(Request $request)
    {
        return $this->report_service->getData($request->all());
    }

    public function print(Request $request)
    {
        $rows = $this->report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;
        $this->log($business_id, 'print');

        return view('admin.reports.fixed_asset_register.print.print', compact('rows', 'request'));
    }

    public function pdf(Request $request)
    {
        $rows = $this->report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;
        $this->log($business_id, 'pdf');
        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView('admin.reports.fixed_asset_register.pdf', compact('rows', 'request'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'landscape'))
            ->stream('fixed-asset-register.pdf');
    }

    public function export(Request $request)
    {
        $rows = $this->report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;
        $this->log($business_id, 'export');

        return Excel::download(new FixedAssetRegisterExport($rows), 'fixed-asset-register.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;
        $this->log($business_id, 'export_csv');

        return Excel::download(new FixedAssetRegisterExport($rows), 'fixed-asset-register.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'fixed_asset_register', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
