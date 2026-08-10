<?php

namespace App\Services\Concrete\Admin;

use App\Support\Print\PrintConfig;
use Illuminate\Support\Facades\Cache;

/**
 * The hot "read" path used on every print/pdf request. Resolves the effective
 * print configuration for a business, backed by the business's own
 * `print_settings` row (auto-created with the Smart Mart ERP defaults on first
 * read via SettingService::getPrintSetting() - see config/print_defaults.php).
 *
 * Registered as a container singleton (see AppServiceProvider::register) so the
 * in-request $memo array is shared across the header/footer/badge partials that
 * all resolve the same business within a single print request.
 */
class PrintSettingResolverService
{
    protected $setting_service;
    protected array $memo = [];

    public function __construct(SettingService $setting_service)
    {
        $this->setting_service = $setting_service;
    }

    public function resolve(?string $business_id): PrintConfig
    {
        $key = $business_id ?? 'null';

        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $data = Cache::remember($this->cacheKey($business_id), 3600, function () use ($business_id) {
            $setting = $this->setting_service->getPrintSetting($business_id);

            return [
                'header_config' => $setting->header_config,
                'footer_config' => $setting->footer_config,
                'page_config' => $setting->page_config,
                'body_config' => $setting->body_config,
            ];
        });

        return $this->memo[$key] = new PrintConfig($data);
    }

    public function forgetCache(?string $business_id): void
    {
        Cache::forget($this->cacheKey($business_id));
        unset($this->memo[$business_id ?? 'null']);
    }

    protected function cacheKey(?string $business_id): string
    {
        return 'print_setting:' . ($business_id ?? 'null');
    }
}
