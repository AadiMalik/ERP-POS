<?php

namespace App\Services\Concrete\Auth;

use App\Models\Otp;
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
     * Uses the business EmailSetting (e.g. Mailtrap SMTP) — never the .env
     * mailpit defaults — so storefront OTP matches admin email settings.
     */
    public function send(string $email, string $purpose, string $business_id): void
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

        $result = $this->email_service->send($business_id, new EmailData([
            'to' => $email,
            'subject' => 'Your verification code',
            'view' => 'emails.otp',
            'data' => [
                'otp' => $code,
                'purpose' => $purpose,
            ],
        ]));

        if (!$result['status']) {
            // Do not leave a usable OTP / cooldown when delivery failed.
            $otp->delete();
            throw new Exception($result['message'] ?: 'Failed to send verification email. Please try again.');
        }
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
