<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Enums\ReferenceType;
use App\Enums\TransactionType;
use App\Exports\StockLedgerExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BrandService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CategoryService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\Reports\StockLedgerReportService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class StockLedgerReportController extends Controller
{
    use ResponseAPI;

    protected $stock_ledger_report_service;
    protected $business_service;
    protected $warehouse_service;
    protected $product_service;
    protected $category_service;
    protected $brand_service;
    protected $document_send_log_service;
    protected $print_setting_resolver;

    public function __construct(
        StockLedgerReportService $stock_ledger_report_service,
        BusinessService $business_service,
        WarehouseService $warehouse_service,
        ProductService $product_service,
        CategoryService $category_service,
        BrandService $brand_service,
        DocumentSendLogService $document_send_log_service,
        PrintSettingResolverService $print_setting_resolver
    ) {
        $this->stock_ledger_report_service = $stock_ledger_report_service;
        $this->business_service = $business_service;
        $this->warehouse_service = $warehouse_service;
        $this->product_service = $product_service;
        $this->category_service = $category_service;
        $this->brand_service = $brand_service;
        $this->document_send_log_service = $document_send_log_service;
        $this->print_setting_resolver = $print_setting_resolver;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $warehouses = $this->warehouse_service->getAllActive();
        $products = $this->product_service->getAllActive();
        $categories = $this->category_service->getAllActive();
        $brands = $this->brand_service->getAllActive();
        $transaction_types = TransactionType::getOptions();
        $reference_types = ReferenceType::getOptions();

        return view('admin.reports.stock_ledger.index', compact(
            'business',
            'warehouses',
            'products',
            'categories',
            'brands',
            'transaction_types',
            'reference_types'
        ));
    }

    public function data(Request $request)
    {
        return $this->stock_ledger_report_service->getData($request->all());
    }

    public function print(Request $request)
    {
        $rows = $this->stock_ledger_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'print');

        return view('admin.reports.stock_ledger.print.print', compact('rows', 'request'));
    }

    public function pdf(Request $request)
    {
        $rows = $this->stock_ledger_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'pdf');

        $print_config = $this->print_setting_resolver->resolve($business_id);

        return Pdf::loadView('admin.reports.stock_ledger.pdf', compact('rows', 'request'))
            ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
            ->stream('stock-ledger.pdf');
    }

    public function export(Request $request)
    {
        $rows = $this->stock_ledger_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export');

        return Excel::download(new StockLedgerExport($rows), 'stock-ledger.xlsx');
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->stock_ledger_report_service->build($request->all());
        $business_id = $request->business_id ?? Auth::user()->business_id;

        $this->log($business_id, 'export_csv');

        return Excel::download(new StockLedgerExport($rows), 'stock-ledger.csv', ExcelFormat::CSV);
    }

    protected function log(string $business_id, string $channel): void
    {
        try {
            $this->document_send_log_service->log($business_id, 'stock_ledger_report', $business_id, $channel, null, 'sent', null, Auth::id());
        } catch (Exception $e) {
            Log::warning('Report audit log failed: ' . $e->getMessage());
        }
    }
}
