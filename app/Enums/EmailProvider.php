<?php

namespace App\Enums;

class  EmailProvider
{
    const SMTP = 'smtp';
    const SENDMAIL = 'sendmail';
    const MAILGUN = 'mailgun';

    public static function getOptions()
    {
        return [
            self::SMTP => 'SMTP',
            self::SENDMAIL => 'Send Mail',
            self::MAILGUN => 'Mail Gun',
        ];
    }
}
