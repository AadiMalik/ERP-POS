<?php

namespace App\Http\Controllers\Api\Offline;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\InventorySetting;
use App\Models\PosSetting;
use App\Services\Concrete\Admin\ThermalPrintSettingResolverService;
use App\Services\Concrete\Api\Offline\OfflineAuthService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    use ResponseAPI;

    protected $auth_service;
    protected $thermal_resolver;

    public function __construct(OfflineAuthService $auth_service, ThermalPrintSettingResolverService $thermal_resolver)
    {
        $this->auth_service = $auth_service;
        $this->thermal_resolver = $thermal_resolver;
    }

    /**
     * Returns POS screen context mirroring PosScreenController::index() POS_CONFIG.
     */
    public function context(Request $request)
    {
        $user = Auth::user();
        $device = $request->attributes->get('pos_device');
        $business_id = $device->business_id;
        $branch_id = $device->branch_id;

        $pos_setting = PosSetting::firstOrCreate(['business_id' => $business_id]);
        $business_setting = BusinessSetting::firstOrCreate(['business_id' => $business_id]);
        $inventory_setting = InventorySetting::firstOrCreate(['business_id' => $business_id]);

        return $this->success('POS context.', [
            'business_id' => $business_id,
            'branch_id' => $branch_id,
            'warehouse_id' => optional($device->register)->warehouse_id,
            'pos_setting' => $pos_setting,
            'allow_negative_stock' => (bool) $inventory_setting->negative_stock,
            'tax_rates_setting' => [
                'overall_tax_rate' => $business_setting->overall_tax_rate,
                'card_tax_rate' => $business_setting->card_tax_rate,
            ],
            'thermal_print_setting' => $this->thermal_resolver->resolve($business_id, $branch_id),
            'user' => $this->auth_service->formatUser($user),
            'permissions' => $this->auth_service->collectPermissions($user),
        ]);
    }
}
