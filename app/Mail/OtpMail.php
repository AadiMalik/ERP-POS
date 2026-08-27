<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $purpose;
    public array $viewData;

    public function __construct(string $otp, string $purpose, array $viewData = [])
    {
        $this->otp = $otp;
        $this->purpose = $purpose;
        $this->viewData = $viewData;
    }

    public function build()
    {
        $data = array_merge([
            'otp' => $this->otp,
            'purpose' => $this->purpose,
            'app_name' => 'Dukanaz',
        ], $this->viewData);

        $logoPath = public_path('assets/img/brand/horizontal-lockup.png');
        if (is_file($logoPath)) {
            $data['logo_path'] = $logoPath;
        }

        $subjects = [
            'password_reset' => 'Reset your Dukanaz password',
            'login' => 'Your Dukanaz sign-in code',
            'onboarding' => 'Verify your Dukanaz account',
        ];

        return $this->subject($subjects[$this->purpose] ?? 'Your Dukanaz verification code')
            ->view('emails.otp', $data)
            ->text('emails.otp-text', $data);
    }
}
