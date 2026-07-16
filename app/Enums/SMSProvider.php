<?php

namespace App\Enums;

class  SMSProvider
{
    const TWILIO = 'twilio';
    const BRANDSMS = 'brandsms';
    const MSG91 = 'msg91';
    const VONAGE = 'vonage';
    const INFOBIP = 'infobip';

    public static function getOptions()
    {
        return [
            self::TWILIO => 'Twilio',
            self::BRANDSMS => 'Brand SMS',
            self::MSG91 => 'Msg91',
            self::VONAGE => 'Vonage',
            self::INFOBIP => 'Infobip',
        ];
    }
}