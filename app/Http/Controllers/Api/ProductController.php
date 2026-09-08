<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\ProductVariationStockService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use ResponseAPI;

    protected $product_service;
    protected $stock_service;

    public function __construct(ProductService $product_service, ProductVariationStockService $stock_service)
    {
        $this->product_service = $product_service;
        $this->stock_service = $stock_service;
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

        // Optional Sanctum user - when present, product payloads include wishlist flags.
        $params['user_id'] = Auth::guard('sanctum')->id();

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

        $user_id = Auth::guard('sanctum')->id();
        $result = $this->product_service->getWebsiteDetail($business_id, $slug, $user_id, $request->query('branch_id'));

        if ($result === null) {
            return $this->error('Product not found', 404);
        }

        return $this->success(Message::FETCH, $result);
    }

    /**
     * Warehouse (and, for batch-tracked variations, batch/expiry)
     * breakdown of a variation's combined branch stock - powers the
     * "Stock: N" click/hover detail on the storefront, loaded on demand so
     * the listing/detail page itself never has to fetch it up front.
     */
    public function stock(Request $request, $business_id, $product_variation_id)
    {
        $validate = Validator::make(
            [
                'business_id' => $business_id,
                'product_variation_id' => $product_variation_id,
                'branch_id' => $request->query('branch_id'),
            ],
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'product_variation_id' => 'required|string|exists:product_variations,product_variation_id',
                'branch_id' => 'required|string|exists:branches,branch_id',
            ]
        );

        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        $product_id = \App\Models\ProductVariation::where('product_variation_id', $product_variation_id)->value('product_id');

        $breakdown = $this->stock_service->getStockBreakdownForBranch(
            $business_id,
            $request->query('branch_id'),
            $product_id,
            $product_variation_id
        );

        return $this->success(Message::FETCH, $breakdown);
    }
}
