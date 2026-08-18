<?php

namespace App\Services\Concrete\Admin;

use App\Support\Print\ThermalPrintConfig;
use Illuminate\Support\Facades\Cache;

/**
 * The hot "read" path used on every order-print request. Resolves the
 * effective thermal receipt configuration for a business, backed by the
 * business's own `thermal_print_settings` row (auto-created with defaults on
 * first read via SettingService::getThermalPrintSetting() - see
 * config/thermal_print_defaults.php).
 *
 * Registered as a container singleton (see AppServiceProvider::register) so
 * the in-request $memo array is shared across every print partial that
 * resolves the same business within a single print request. Mirrors
 * PrintSettingResolverService exactly.
 */
class ThermalPrintSettingResolverService
{
    protected $setting_service;
    protected array $memo = [];

    public function __construct(SettingService $setting_service)
    {
        $this->setting_service = $setting_service;
    }

    public function resolve(?string $business_id): ThermalPrintConfig
    {
        $key = $business_id ?? 'null';

        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $data = Cache::remember($this->cacheKey($business_id), 3600, function () use ($business_id) {
            $setting = $this->setting_service->getThermalPrintSetting($business_id);

            return [
                'is_enabled' => $setting->is_enabled,
                'paper_width_mm' => $setting->paper_width_mm,
                'field_config' => $setting->field_config,
                'footer_config' => $setting->footer_config,
            ];
        });

        return $this->memo[$key] = new ThermalPrintConfig($data);
    }

    public function forgetCache(?string $business_id): void
    {
        Cache::forget($this->cacheKey($business_id));
        unset($this->memo[$business_id ?? 'null']);
    }

    protected function cacheKey(?string $business_id): string
    {
        return 'thermal_print_setting:' . ($business_id ?? 'null');
    }
}
