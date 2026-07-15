<?php

namespace App\Services\Concrete\Email\Providers;

use App\Mail\CommonMail;
use App\Services\Concrete\Email\DTO\EmailData;
use App\Services\Concrete\Email\Contracts\EmailProviderInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class SendmailProvider implements EmailProviderInterface
{
    protected $setting;

    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    public function send(EmailData $email): bool
    {
        Config::set('mail.default', 'sendmail');
        Config::set(
            'mail.mailers.sendmail.path',
            $this->setting->mail_host
                ?: '/usr/sbin/sendmail -bs'
        );
        Config::set('mail.from.address', $this->setting->mail_from_address);
        Config::set('mail.from.name', $this->setting->mail_from_name);
        Mail::to($email->to)
            ->cc($email->cc)
            ->bcc($email->bcc)
            ->send(new CommonMail($email));
        return true;
    }
}
