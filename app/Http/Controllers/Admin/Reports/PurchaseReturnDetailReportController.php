<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\PurchaseReturnDetailExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\Reports\PurchaseReturnDetailReportService;
use App\Services\Concrete\Admin\SupplierService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseReturnDetailReportController extends Controller
{
    use ResponseAPI;

    protected $purchase_return_detail_report_service;
    protected $business_service;
    protected $supplier_service;
    protected $warehouse_service;
    protected $product_service;
    protected $document_send_log_service;
    protected $print_setting_resolver;

    public function __construct(
        PurchaseReturnDetailReportService $purchase_return_detail_report_service,
        BusinessService $business_service,
        SupplierService $supplier_service,
        WarehouseService $warehouse_service,
        ProductService $product_service,
        DocumentSendLogService $document_send_log_service,
        PrintSettingResolverService $print_setting_resolver
    ) {
        $this->middleware('permission:reports.purchase-return-detail.view');
        $this->middleware('permission:reports.purchase-return-detail.print')->only(['print']);
        $this->middleware('permission:reports.purchase-return-detail.pdf')->only(['pdf']);
        $this->middleware('permission:reports.purchase-return-detail.export')->only(['export']);
        $this->middleware('permission:reports.purchase-return-detail.export-csv')->only(['exportCsv']);

        $this->purchase_return_detail_report_service = $purchase_return_detail_report_service;
        $this->business_service = $business_service;
        $this->supplier_service = $supplier_service;
        $this->warehouse_service = $warehouse_service;
        $this->product_service = $product_service;
        $this->document_send_log_service = $document_send_log_service;
        $this->print_setting_resolver = $print_setting_resolver;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $products = $this->product_service->getAllActive();

        return view('admin.reports.purchase_return_detail.index', compact('business', 'suppliers', 'warehouses', 'products'));
    }

    public function data(Request $request)
    {
        return $this->purchase_return_detail_report_service->getData($request->all());
    }

    public function print(Request $request)
    {
        $rows = $this->purchase_return_detail_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'print');

        return view('admin.reports.purchase_return_detail.print.print', compact('rows', 'request'));
    }

    public function pdf(Request $request)
    {
        $rows = $this->purchase_return_detail_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView('admin.reports.purchase_return_detail.pdf', compact('rows', 'request'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
            ->stream('purchase-return-detail.pdf');
    }

    public function export(Request $request)
    {
        $rows = $this->purchase_return_detail_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download(new PurchaseReturnDetailExport($rows), 'purchase-return-detail.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->purchase_return_detail_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download(new PurchaseReturnDetailExport($rows), 'purchase-return-detail.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'purchase_return_detail_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
