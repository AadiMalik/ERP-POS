<?php

namespace App\Http\Controllers\Admin\Reports\Orders;

use App\Enums\RoleNames;
use App\Exports\Orders\VariationSalesReportExport;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\OrderSourceService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\Reports\Orders\VariationSalesReportService;
use Illuminate\Http\Request;

class VariationSalesReportController extends BaseOrderReportController
{
    public function __construct(
        VariationSalesReportService $service,
        protected BranchService $branch_service,
        protected OrderSourceService $order_source_service,
        protected BusinessService $business_service,
        protected ProductService $product_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        parent::__construct($print_setting_resolver, $document_send_log_service);
        $this->service = $service;
    }

    protected function permissionName(): string
    {
        return 'reports.variation-sales.view';
    }

    protected function reportKey(): string
    {
        return 'variation-sales';
    }

    protected function viewDir(): string
    {
        return 'variation_sales';
    }

    public function index(Request $request)
    {
        $business = RoleNames::SUPERADMIN == getRoleName() ? $this->business_service->getAll() : null;
        $branches = $this->branch_service->getAllActive();
        $order_sources = $this->order_source_service->getAllActive();
        $products = $this->product_service->getAllActive();

        return view('admin.reports.variation_sales.index', compact('business', 'branches', 'order_sources', 'products'));
    }

    protected function exportInstance($rows)
    {
        return new VariationSalesReportExport($rows);
    }
}
