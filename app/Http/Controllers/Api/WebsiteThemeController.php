<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\SettingService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebsiteThemeController extends Controller
{
    use ResponseAPI;

    protected $setting_service;

    public function __construct(SettingService $setting_service)
    {
        $this->setting_service = $setting_service;
    }

    /**
     * Public storefront endpoint - the Vue frontend calls this with its
     * .env-configured business_id before the app renders, to apply that
     * business's website theme (colors, typography, button style) globally.
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

        $setting = $this->setting_service->getWebsiteThemeSetting($business_id);
        $config = $this->setting_service->resolveWebsiteThemeConfig($setting);

        return $this->success(Message::FETCH, $config);
    }
}
