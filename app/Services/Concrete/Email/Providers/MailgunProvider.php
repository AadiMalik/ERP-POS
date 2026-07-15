<?php

namespace App\Services\Concrete\Email\Providers;

use App\Mail\CommonMail;
use App\Services\Concrete\Email\DTO\EmailData;
use App\Services\Concrete\Email\Contracts\EmailProviderInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class MailgunProvider implements EmailProviderInterface
{
    protected $setting;

    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    public function send(EmailData $email): bool
    {
        Config::set('mail.default', 'mailgun');

        Config::set('services.mailgun.domain', $this->setting->mail_host);
        Config::set('services.mailgun.secret', $this->setting->mail_password);

        Config::set('mail.from.address', $this->setting->mail_from_address);
        Config::set('mail.from.name', $this->setting->mail_from_name);

        Mail::to($email->to)
            ->cc($email->cc)
            ->bcc($email->bcc)
            ->send(new CommonMail($email));

        return true;
    }
}
