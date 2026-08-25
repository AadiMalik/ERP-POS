<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\ProductService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use ResponseAPI;

    protected $product_service;

    public function __construct(ProductService $product_service)
    {
        $this->product_service = $product_service;
    }

    /**
     * Public storefront endpoint - filterable/sortable/paginated product
     * listing for a business, plus (unfiltered, page 1 only) curated
     * homepage sections, used by the Vue frontend instead of hard-coded
     * product data.
     */
    public function index(Request $request, $business_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );

        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        $params = $request->only([
            'search',
            'category_id',
            'sub_category_id',
            'brand_id',
            'min_price',
            'max_price',
            'in_stock',
            'on_sale',
            'sort',
            'page',
            'per_page',
            'branch_id',
        ]);

        $result = $this->product_service->getWebsiteListing($business_id, $params);

        return $this->success(Message::FETCH, $result);
    }

    /**
     * Public storefront endpoint - single product detail by slug.
     */
    public function show(Request $request, $business_id, $slug)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );

        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        $result = $this->product_service->getWebsiteDetail($business_id, $slug);

        if ($result === null) {
            return $this->error('Product not found', 404);
        }

        return $this->success(Message::FETCH, $result);
    }
}
