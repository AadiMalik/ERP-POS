<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\WebsiteHomeService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebsiteHomeController extends Controller
{
    use ResponseAPI;

    protected $home_service;

    public function __construct(WebsiteHomeService $home_service)
    {
        $this->home_service = $home_service;
    }

    /**
     * Single optimized homepage payload - business/global settings, active
     * theme, navigation, CMS sections (hero/about/why-shop/banners),
     * product groups (data from Products, config from CMS), social links,
     * FAQs and reviews. Avoids the Vue frontend making a separate request
     * per homepage section.
     */
    public function show(Request $request, $business_id)
    {
        $validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );

        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 404);
        }

        return $this->success(Message::FETCH, $this->home_service->build($business_id));
    }
}
