<?php

namespace App\Services\Concrete\Email\Providers;


use App\Mail\CommonMail;

use App\Services\Concrete\Email\DTO\EmailData;

use App\Services\Concrete\Email\Contracts\EmailProviderInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class SMTPProvider implements EmailProviderInterface
{

    protected $setting;
    

    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    public function send(
        EmailData $email
    ): bool {

        Config::set('mail.default', 'smtp');

        Config::set('mail.mailers.smtp.transport', 'smtp');

        Config::set('mail.mailers.smtp.host', $this->setting->mail_host);

        Config::set('mail.mailers.smtp.port', $this->setting->mail_port);

        Config::set('mail.mailers.smtp.username', $this->setting->mail_username);

        Config::set('mail.mailers.smtp.password', $this->setting->mail_password);

        Config::set(
            'mail.mailers.smtp.encryption',
            $this->setting->mail_encryption == 'none'
                ? null
                : $this->setting->mail_encryption
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
