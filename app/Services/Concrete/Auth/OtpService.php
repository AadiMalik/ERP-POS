<?php

namespace App\Services\Concrete\Auth;

use App\Models\Business;
use App\Models\Otp;
use App\Models\User;
use App\Models\WebsiteThemeSetting;
use App\Services\Concrete\Email\DTO\EmailData;
use App\Services\Concrete\Email\EmailService;
use Exception;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    const EXPIRY_MINUTES = 10;
    const MAX_VERIFY_ATTEMPTS = 5;
    const RESEND_COOLDOWN_SECONDS = 60;
    const MAX_SENDS_PER_HOUR = 5;

    protected EmailService $email_service;

    public function __construct(EmailService $email_service)
    {
        $this->email_service = $email_service;
    }

    /**
     * Generate, store, and email a fresh OTP for the given email + purpose.
     * $channel `erp` uses Dukanaz branding; `storefront` uses the tenant
     * business logo/name/colors with a Powered by Dukanaz footer.
     */
    public function send(string $email, string $purpose, ?string $business_id = null, string $channel = 'erp'): void
    {
        $email = strtolower(trim($email));

        $lastSent = Otp::where('email', $email)
            ->where('purpose', $purpose)
            ->orderByDesc('created_at')
            ->first();

        if ($lastSent && $lastSent->created_at->diffInSeconds(now()) < self::RESEND_COOLDOWN_SECONDS) {
            throw new Exception('Please wait before requesting another code.');
        }

        $sentInLastHour = Otp::where('email', $email)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($sentInLastHour >= self::MAX_SENDS_PER_HOUR) {
            throw new Exception('Too many code requests. Please try again later.');
        }

        Otp::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['expires_at' => now()]);

        $code = (string) random_int(100000, 999999);

        $otp = Otp::create([
            'email' => $email,
            'otp_hash' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'attempts' => 0,
            'resend_count' => $sentInLastHour,
            'ip_address' => request()?->ip(),
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        $fullName = $user && filled($user->name) ? trim($user->name) : null;
        $firstName = $fullName ? explode(' ', $fullName)[0] : null;

        $brand = $this->brandContext($channel, $business_id, $purpose);

        $emailData = new EmailData([
            'to' => $email,
            'subject' => $brand['subject'],
            'view' => $brand['view'],
            'text_view' => $brand['text_view'],
            'data' => array_merge($brand['data'], [
                'otp' => $code,
                'purpose' => $purpose,
                'user_name' => $fullName,
                'first_name' => $firstName,
                'account_email' => $email,
                'expiry_minutes' => self::EXPIRY_MINUTES,
                'year' => date('Y'),
            ]),
        ]);

        $result = $this->deliver($emailData, $business_id);

        if (!$result['status']) {
            // Do not leave a usable OTP / cooldown when delivery failed.
            $otp->delete();
            throw new Exception($result['message'] ?: 'Failed to send verification email. Please try again.');
        }
    }

    /**
     * Prefer the tenant's EmailSetting; if that is missing or fails, use the
     * platform-level channel so password-reset still works for Super Admin
     * (null business_id) and businesses that have not configured SMTP yet.
     */
    protected function deliver(EmailData $emailData, ?string $business_id): array
    {
        $businessResult = null;

        if ($business_id) {
            $businessResult = $this->email_service->send($business_id, $emailData);
            if ($businessResult['status']) {
                return $businessResult;
            }
        }

        $platformResult = $this->email_service->sendPlatform($emailData);
        if ($platformResult['status']) {
            return $platformResult;
        }

        return $businessResult ?? $platformResult;
    }

    /**
     * ERP OTPs are Dukanaz-branded. Storefront OTPs (signup, login, password
     * reset from the website API) use the tenant business identity (logo,
     * name, theme colors) and a Powered by Dukanaz footer.
     */
    protected function brandContext(string $channel, ?string $business_id, string $purpose): array
    {
        if ($channel === 'storefront' && $business_id) {
            return $this->storefrontBrand($business_id, $purpose);
        }

        $logoPath = public_path('assets/img/brand/horizontal-lockup.png');
        $subjects = [
            'password_reset' => 'Reset your Dukanaz password',
            'login' => 'Your Dukanaz sign-in code',
            'onboarding' => 'Verify your Dukanaz account',
        ];

        return [
            'subject' => $subjects[$purpose] ?? 'Your Dukanaz verification code',
            'view' => 'emails.otp',
            'text_view' => 'emails.otp-text',
            'data' => [
                'app_name' => 'Dukanaz',
                'tagline' => 'Business Software, Unified',
                'logo_path' => is_file($logoPath) ? $logoPath : null,
                'logo_url' => asset('public/assets/img/brand/horizontal-lockup.png'),
                'login_url' => url('/login'),
                'brand_primary' => '#0B1B32',
                'brand_accent' => '#2DD4BF',
            ],
        ];
    }

    protected function storefrontBrand(string $business_id, string $purpose): array
    {
        $business = Business::find($business_id);
        $name = $business->name ?? 'our store';
        $theme = WebsiteThemeSetting::where('business_id', $business_id)->first();

        $logoPath = null;
        $logoUrl = null;
        if ($business && !empty($business->logo)) {
            $file = public_path('uploads/business/' . $business->logo);
            if (is_file($file)) {
                $logoPath = $file;
            }
            $logoUrl = asset('public/uploads/business/' . $business->logo);
        }

        $primary = ($theme && filled($theme->primary_color)) ? $theme->primary_color : '#0B1B32';
        $accent = ($theme && filled($theme->accent_color)) ? $theme->accent_color : $primary;
        $heading = ($theme && filled($theme->heading_color)) ? $theme->heading_color : $primary;

        $subjects = [
            'password_reset' => "Reset your {$name} password",
            'login' => "Your {$name} sign-in code",
            'onboarding' => "Verify your {$name} account",
        ];

        return [
            'subject' => $subjects[$purpose] ?? "Your {$name} verification code",
            'view' => 'emails.otp-storefront',
            'text_view' => 'emails.otp-storefront-text',
            'data' => [
                'app_name' => $name,
                'tagline' => null,
                'logo_path' => $logoPath,
                'logo_url' => $logoUrl,
                'login_url' => null,
                'brand_primary' => $heading,
                'brand_accent' => $accent,
            ],
        ];
    }

    /**
     * Verify a submitted code against the most recent unconsumed OTP for the
     * email + purpose. Throws with a generic message on any failure so the
     * caller can't distinguish "wrong code" from "expired"/"not found".
     */
    public function verify(string $email, string $code, string $purpose): bool
    {
        $email = strtolower(trim($email));

        $otp = Otp::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->orderByDesc('created_at')
            ->first();

        if (!$otp || $otp->expires_at->isPast()) {
            throw new Exception('This code is invalid or has expired. Please request a new one.');
        }

        if ($otp->attempts >= self::MAX_VERIFY_ATTEMPTS) {
            $otp->update(['expires_at' => now()]);
            throw new Exception('Too many incorrect attempts. Please request a new code.');
        }

        if (!Hash::check($code, $otp->otp_hash)) {
            $otp->increment('attempts');
            throw new Exception('The code you entered is incorrect.');
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }
}
