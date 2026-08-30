<?php

namespace App\Services\Concrete\Api\Mobile;

use App\Services\Concrete\Admin\SettingService;

/**
 * Mobile store bootstrap config — theme, public website settings, and
 * payment methods (same payload shape as the website storefront).
 */
class MobileStoreConfigService
{
    protected $setting_service;
    protected $checkout_service;

    public function __construct(SettingService $setting_service, MobileCheckoutService $checkout_service)
    {
        $this->setting_service = $setting_service;
        $this->checkout_service = $checkout_service;
    }

    public function theme(string $business_id): array
    {
        $setting = $this->setting_service->getWebsiteThemeSetting($business_id);

        return $this->setting_service->resolveWebsiteThemeConfig($setting);
    }

    public function settings(string $business_id): array
    {
        return $this->setting_service->getWebsitePublicSettings($business_id);
    }

    public function paymentMethods(string $business_id): array
    {
        $methods = $this->checkout_service->getWebsitePaymentMethods($business_id);
        $settings = $this->settings($business_id);

        return [
            'methods' => $methods,
            'bank_details' => $settings['bank_details'] ?? null,
        ];
    }
}
