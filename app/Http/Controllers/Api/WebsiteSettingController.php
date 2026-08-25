<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\SettingService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebsiteSettingController extends Controller
{
    use ResponseAPI;

    protected $setting_service;

    public function __construct(SettingService $setting_service)
    {
        $this->setting_service = $setting_service;
    }

    /**
     * Public storefront endpoint - global website settings (business
     * identity, currency, SEO, favicon, social links, WhatsApp, hours)
     * assembled from `businesses`, `accounting_settings` and
     * `website_theme_settings`. Consumed by the Vue frontend before render,
     * alongside the existing website-theme endpoint.
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

        $settings = $this->setting_service->getWebsitePublicSettings($business_id);

        return $this->success(Message::FETCH, $settings);
    }
}
