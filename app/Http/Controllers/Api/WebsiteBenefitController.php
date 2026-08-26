<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\WebsiteBenefitService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebsiteBenefitController extends Controller
{
    use ResponseAPI;

    protected $benefit_service;

    public function __construct(WebsiteBenefitService $benefit_service)
    {
        $this->benefit_service = $benefit_service;
    }

    public function index(Request $request, $business_id, $group = null)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );

        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        return $this->success(Message::FETCH, $this->benefit_service->getActivePublicByBusiness($business_id, $group));
    }
}
