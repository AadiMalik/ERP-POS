<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BrandService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    use ResponseAPI;

    protected $brand_service;

    public function __construct(BrandService $brand_service)
    {
        $this->brand_service = $brand_service;
    }

    /**
     * Public storefront endpoint - active brands for a business, used by
     * the Vue frontend instead of hard-coded brand data.
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

        $brands = $this->brand_service->getActivePublicByBusiness($business_id);

        return $this->success(Message::FETCH, $brands);
    }
}
