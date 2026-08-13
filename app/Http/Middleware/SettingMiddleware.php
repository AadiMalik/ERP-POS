<?php

namespace App\Http\Middleware;

use App\Enums\RoleNames;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && getRoleName() != RoleNames::SUPERADMIN) {

            $business = Auth::user()->business;

            session([
                'business_setting'   => $business->businessSetting?->toArray() ?? [],
                'accounting_setting' => $business->accountingSetting?->toArray() ?? [],
                'customer_setting'   => $business->customerSetting?->toArray() ?? [],
                'supplier_setting'   => $business->supplierSetting?->toArray() ?? [],
                'inventory_setting'  => $business->inventorySetting?->toArray() ?? [],
                'email_setting'      => $business->emailSetting?->toArray() ?? [],
                'sms_setting'        => $business->smsSetting?->toArray() ?? [],
                'fbr_setting'        => $business->fbrSetting?->toArray() ?? [],
                'whatsapp_setting'   => $business->whatsappSetting?->toArray() ?? [],
                'barcode_setting'    => $business->barcodeSetting?->toArray() ?? [],
                'theme_setting'      => $business->themeSetting?->toArray() ?? [],
                'pos_setting'        => $business->posSetting?->toArray() ?? [],
                'pra_setting'        => $business->praSetting?->toArray() ?? [],
            ]);
        }
        return $next($request);
    }
}
