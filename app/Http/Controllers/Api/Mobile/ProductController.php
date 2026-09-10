<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Mobile\MobileCatalogService;
use App\Services\Concrete\Api\ProductShareService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use ResponseAPI;

    protected $catalog_service;
    protected $share_service;

    public function __construct(MobileCatalogService $catalog_service, ProductShareService $share_service)
    {
        $this->catalog_service = $catalog_service;
        $this->share_service = $share_service;
    }

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

        $params['user_id'] = Auth::guard('sanctum')->id();

        return $this->success(Message::FETCH, $this->catalog_service->products($business_id, $params));
    }

    public function show(Request $request, $business_id, $slug)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );

        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        $result = $this->catalog_service->product(
            $business_id,
            $slug,
            Auth::guard('sanctum')->id(),
            $request->query('branch_id')
        );

        if ($result === null) {
            return $this->error('Product not found', 404);
        }

        return $this->success(Message::FETCH, $result);
    }

    /**
     * Warehouse/batch breakdown of a variation's combined branch stock -
     * mobile counterpart of Api\ProductController::stock().
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

        $breakdown = $this->catalog_service->stock($business_id, $request->query('branch_id'), $product_variation_id);

        return $this->success(Message::FETCH, $breakdown);
    }

    /**
     * Mobile counterpart of Api\ProductController::share() - same
     * ProductShareService, same optional-auth (guest shares still log,
     * just without an identity).
     */
    public function share(Request $request, $business_id, $product_id)
    {
        $validate = Validator::make(
            [
                'business_id' => $business_id,
                'product_id' => $product_id,
                'platform' => $request->input('platform'),
            ],
            [
                'business_id' => 'required|string|exists:businesses,business_id',
                'product_id' => 'required|string|exists:products,product_id',
                'platform' => 'required|string|in:' . implode(',', ProductShareService::PLATFORMS),
            ]
        );

        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 422);
        }

        try {
            $result = $this->share_service->record($business_id, $product_id, Auth::guard('sanctum')->id(), $request->input('platform'));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::SAVE, $result);
    }
}
