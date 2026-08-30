<?php

namespace App\Services\Concrete\Api\Mobile;

use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BrandService;
use App\Services\Concrete\Admin\CategoryService;
use App\Services\Concrete\Admin\ProductService;

/**
 * Mobile catalog reads — branches, categories, brands, and products for a
 * business. Delegates to the same Admin public helpers used by the website API.
 */
class MobileCatalogService
{
    protected $branch_service;
    protected $category_service;
    protected $brand_service;
    protected $product_service;

    public function __construct(
        BranchService $branch_service,
        CategoryService $category_service,
        BrandService $brand_service,
        ProductService $product_service
    ) {
        $this->branch_service = $branch_service;
        $this->category_service = $category_service;
        $this->brand_service = $brand_service;
        $this->product_service = $product_service;
    }

    public function branches(string $business_id)
    {
        return $this->branch_service->getActivePublicByBusiness($business_id);
    }

    public function categories(string $business_id)
    {
        return $this->category_service->getActivePublicByBusiness($business_id);
    }

    public function brands(string $business_id)
    {
        return $this->brand_service->getActivePublicByBusiness($business_id);
    }

    public function products(string $business_id, array $params)
    {
        return $this->product_service->getWebsiteListing($business_id, $params);
    }

    public function product(string $business_id, string $slug, ?int $user_id = null)
    {
        return $this->product_service->getWebsiteDetail($business_id, $slug, $user_id);
    }
}
