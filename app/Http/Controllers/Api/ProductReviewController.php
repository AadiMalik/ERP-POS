<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\ProductReviewService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductReviewController extends Controller
{
    use ResponseAPI;

    protected $review_service;

    public function __construct(ProductReviewService $review_service)
    {
        $this->review_service = $review_service;
    }

    public function index(Request $request, $business_id, $product_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );

        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        return $this->success(Message::FETCH, $this->review_service->getPublicByProduct($business_id, $product_id));
    }

    /**
     * Requires an authenticated customer (auth:sanctum) - review identity
     * comes from the logged-in customer, not a free-text field.
     */
    public function store(Request $request, $business_id)
    {
        $rules = [
            'product_id' => 'required|string|exists:products,product_id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 422);
        }

        try {
            $review = $this->review_service->submit($business_id, [
                'product_id' => $request->product_id,
                'customer_id' => Auth::id(),
                'reviewer_name' => Auth::user()->name ?? 'Customer',
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(Message::SAVE, $review);
    }
}
