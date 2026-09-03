<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Enums\RoleNames;
use App\Exports\LoyaltyHistoryReportExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\Reports\LoyaltyHistoryReportService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Customer Loyalty History report - lists customer_loyalty_transactions
 * directly (not order-table-rooted), so it does not extend
 * BaseOrderReportController. Print/PDF/Excel/CSV mechanics below are copied
 * from BaseOrderReportController's print()/pdf()/export()/exportCsv()
 * (same DomPDF/Laravel-Excel calls, same audit log), just called directly
 * since the underlying query source differs from every Orders report.
 * Mirrors CustomerLedgerReportController for placement/permission-gating
 * style, being the closest customer-scoped, non-order-table report.
 */
class LoyaltyHistoryReportController extends Controller
{
    use ResponseAPI;

    public function __construct(
        protected LoyaltyHistoryReportService $service,
        protected BusinessService $business_service,
        protected CustomerService $customer_service,
        protected DocumentSendLogService $document_send_log_service,
        protected PrintSettingResolverService $print_setting_resolver
    ) {
        $this->middleware('permission:reports.customer-loyalty-report.view');
        $this->middleware('permission:reports.customer-loyalty-report.print')->only(['print']);
        $this->middleware('permission:reports.customer-loyalty-report.pdf')->only(['pdf']);
        $this->middleware('permission:reports.customer-loyalty-report.export')->only(['export']);
        $this->middleware('permission:reports.customer-loyalty-report.export-csv')->only(['exportCsv']);
    }

    public function index()
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $customers = $this->customer_service->getAllActive();

        return view('admin.reports.customer_loyalty_report.index', compact('business', 'customers'));
    }

    public function data(Request $request)
    {
        return $this->service->getData($request->all());
    }

    public function print(Request $request)
    {
        $rows = $this->service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'print');

        return view('admin.reports.customer_loyalty_report.print.print', compact('rows', 'request'));
    }

    public function pdf(Request $request)
    {
        $rows = $this->service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView('admin.reports.customer_loyalty_report.pdf', compact('rows', 'request'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
            ->stream('customer-loyalty-report.pdf');
    }

    public function export(Request $request)
    {
        $rows = $this->service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download(new LoyaltyHistoryReportExport($rows), 'customer-loyalty-report.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download(new LoyaltyHistoryReportExport($rows), 'customer-loyalty-report.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'customer_loyalty_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
