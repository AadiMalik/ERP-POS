<?php

namespace App\Http\Controllers\Admin\Reports\Hrm\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Shared plumbing for every Attendance report - identical shape to
 * BaseEmployeeReportController (see that class for the rationale).
 */
abstract class BaseAttendanceReportController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(
        protected PrintSettingResolverService $print_setting_resolver,
        protected DocumentSendLogService $document_send_log_service
    ) {
        $this->middleware('permission:' . $this->permissionName());
    }

    abstract protected function permissionName(): string;

    abstract protected function reportKey(): string;

    abstract protected function viewDir(): string;

    abstract protected function exportInstance($rows);

    abstract public function index(Request $request);

    public function data(Request $request)
    {
        return $this->service->getData($request->all());
    }

    public function print(Request $request)
    {
        $rows = $this->service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'print');

        return view("admin.reports.{$this->viewDir()}.print.print", compact('rows', 'request'));
    }

    public function pdf(Request $request)
    {
        $rows = $this->service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView("admin.reports.{$this->viewDir()}.pdf", compact('rows', 'request'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'landscape'))
            ->stream($this->reportKey() . '.pdf');
    }

    public function export(Request $request)
    {
        $rows = $this->service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download($this->exportInstance($rows), $this->reportKey() . '.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download($this->exportInstance($rows), $this->reportKey() . '.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, str_replace('-', '_', $this->reportKey()), $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
