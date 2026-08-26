<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\WebsiteFaqService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebsiteFaqController extends Controller
{
    use ResponseAPI;

    protected $faq_service;

    public function __construct(WebsiteFaqService $faq_service)
    {
        $this->faq_service = $faq_service;
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

        return $this->success(Message::FETCH, $this->faq_service->getActivePublicByBusiness($business_id));
    }
}
