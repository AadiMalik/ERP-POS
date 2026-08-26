<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\ProductReviewService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    use ResponseAPI;

    protected $review_service;

    public function __construct(ProductReviewService $review_service)
    {
        $this->middleware('permission:product-review.view')->only(['index', 'getData']);
        $this->middleware('permission:product-review.status')->only(['status']);
        $this->middleware('permission:product-review.delete')->only(['destroy']);

        $this->review_service = $review_service;
    }

    public function index()
    {
        return view('admin.product_review.index');
    }

    public function getData(Request $request)
    {
        return $this->review_service->getData($request->all());
    }

    public function status($id)
    {
        try {
            $this->review_service->status($id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $this->review_service->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
