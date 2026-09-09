<?php

namespace App\Services\Concrete\Admin;

use App\Models\BusinessSetting;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Cache;

/**
 * Single shared tax resolver used by every sales channel (POS, offline/
 * desktop POS, website, mobile app) so they all apply the exact same branch
 * rate/type - replaces the two independent resolveTaxPercent() copies that
 * used to live separately in OrderService and WebsiteCartService. Mirrors
 * ThermalPrintSettingResolverService's memo + Cache::remember shape.
 */
class TaxSettingResolverService
{
    protected $setting_service;
    protected array $memo = [];

    public function __construct(SettingService $setting_service)
    {
        $this->setting_service = $setting_service;
    }

    /**
     * Returns ['rate' => float, 'tax_type' => 'inclusive'|'exclusive'] for a
     * branch. The Card Tax Rate only applies when $payment_method_ids is
     * non-empty AND every one of them is a card-type payment method (moved
     * verbatim from the old OrderService::resolveTaxPercent()); an empty
     * array (e.g. a website/mobile cart preview, no payment chosen yet)
     * always resolves to the Overall rate, matching today's behavior.
     */
    public function resolve(string $business_id, ?string $branch_id, array $payment_method_ids = []): array
    {
        $key = $business_id . ':' . ($branch_id ?? 'null');

        if (!isset($this->memo[$key])) {
            $this->memo[$key] = Cache::remember($this->cacheKey($business_id, $branch_id), 3600, function () use ($business_id, $branch_id) {
                $row = $branch_id
                    ? $this->setting_service->getBranchTaxSetting($business_id, $branch_id)
                    : null;

                // Defensive fallback only - every order/cart is expected to
                // already carry a branch_id by the time tax is resolved;
                // this only guards a stray code path with no branch context.
                if (!$row) {
                    $legacy = BusinessSetting::where('business_id', $business_id)->first();

                    return [
                        'overall_tax_rate' => (float) ($legacy->overall_tax_rate ?? 0),
                        'card_tax_rate' => (float) ($legacy->card_tax_rate ?? 0),
                        'tax_type' => 'exclusive',
                    ];
                }

                return [
                    'overall_tax_rate' => (float) $row->overall_tax_rate,
                    'card_tax_rate' => (float) $row->card_tax_rate,
                    'tax_type' => $row->tax_type,
                ];
            });
        }

        $setting = $this->memo[$key];

        $is_fully_card = !empty($payment_method_ids) && collect($payment_method_ids)->every(function ($payment_method_id) {
            $method = PaymentMethod::find($payment_method_id);

            return $method && $method->type === 'card';
        });

        return [
            'rate' => $is_fully_card ? $setting['card_tax_rate'] : $setting['overall_tax_rate'],
            'tax_type' => $setting['tax_type'],
        ];
    }

    public function forgetCache(string $business_id, string $branch_id): void
    {
        Cache::forget($this->cacheKey($business_id, $branch_id));
        unset($this->memo[$business_id . ':' . $branch_id]);
    }

    protected function cacheKey(string $business_id, ?string $branch_id): string
    {
        return 'branch_tax_setting:' . $business_id . ':' . ($branch_id ?? 'null');
    }
}
