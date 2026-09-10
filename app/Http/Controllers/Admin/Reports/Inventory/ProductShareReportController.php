<?php

namespace App\Http\Controllers\Admin\Reports\Inventory;

use App\Exports\InventoryReportExport;
use App\Services\Concrete\Admin\BrandService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CategoryService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\Reports\Inventory\ProductShareReportService;
use App\Services\Concrete\Api\ProductShareService;
use Illuminate\Http\Request;

class ProductShareReportController extends BaseInventoryReportController
{
    public function __construct(
        ProductShareReportService $service,
        protected BusinessService $business_service,
        protected ProductService $product_service,
        protected CategoryService $category_service,
        protected BrandService $brand_service,
        PrintSettingResolverService $print_setting_resolver,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->service = $service;
        parent::__construct($print_setting_resolver, $document_send_log_service);
    }

    protected function permissionName(): string { return 'reports.product-shares.view'; }
    protected function reportKey(): string { return 'product-shares'; }
    protected function viewDir(): string { return 'product_shares'; }

    protected function exportInstance($rows)
    {
        $headings = ['Product', 'Category', 'Brand', 'Total Shares'];
        $keys = ['product_name', 'category_name', 'brand_name', 'total_shares'];

        foreach (ProductShareService::PLATFORMS as $platform) {
            $headings[] = ucwords(str_replace('_', ' ', $platform));
            $keys[] = $platform;
        }

        $headings[] = 'Last Shared';
        $keys[] = 'last_shared_at';

        return new InventoryReportExport($rows, $headings, $keys);
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $categories = $this->category_service->getAllActive();
        $brands = $this->brand_service->getAllActive();
        $platforms = ProductShareService::PLATFORMS;
        $report_title = 'Product Shares Report';

        return view('admin.reports.inventory.product_shares.index', compact(
            'business', 'products', 'categories', 'brands', 'platforms', 'report_title'
        ));
    }
}
