<?php

namespace App\Traits;

use App\Models\LoginSecuritySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Google reCAPTCHA v2 server-side verification, shared by the storefront and
 * mobile customer auth controllers to gate OTP requests and password login.
 * CAPTCHA is a per-business toggle (Settings > Social Login & Security) -
 * a business that hasn't turned it on is simply not required to send a
 * captcha_token at all.
 */
trait VerifiesCaptcha
{
    protected function verifyCaptcha(Request $request, string $businessId): bool
    {
        $setting = LoginSecuritySetting::where('business_id', $businessId)->first();
        if (!$setting || !$setting->is_captcha_enabled) {
            return true;
        }

        $token = $request->input('captcha_token');
        if (!$token) {
            return false;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $setting->recaptcha_secret_key,
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        return (bool) $response->json('success');
    }
}
