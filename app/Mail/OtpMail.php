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

    public function __construct(string $otp, string $purpose)
    {
        $this->otp = $otp;
        $this->purpose = $purpose;
    }

    public function build()
    {
        return $this->subject('Your verification code')
            ->view('emails.otp', [
                'otp' => $this->otp,
                'purpose' => $this->purpose,
            ]);
    }
}
