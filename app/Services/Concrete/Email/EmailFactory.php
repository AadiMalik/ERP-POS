<?php

namespace App\Services\Concrete\Email;

use Exception;
use App\Models\EmailSetting;
use App\Services\Concrete\Email\Providers\SMTPProvider;
use App\Services\Concrete\Email\Providers\MailgunProvider;
use App\Services\Concrete\Email\Providers\SendmailProvider;

class EmailFactory
{

    public static function make(object $setting)
    {
        if (!$setting) {
            throw new Exception("Email setting not found.");
        }

        if (!$setting->enable_email_notifications) {
            throw new Exception("Email disabled.");
        }
        switch ($setting->mail_mailer) {
            case 'smtp':
                return new SMTPProvider($setting);
            case 'mailgun':
                return new MailgunProvider($setting);
            case 'sendmail':
                return new SendmailProvider($setting);
            default:
                throw new Exception("Unsupported provider.");
        }
    }
}
