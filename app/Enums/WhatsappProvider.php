<?php

namespace App\Enums;

class  WhatsappProvider
{
    const META = 'meta';
    const GREENAPI = 'greenapi';
    const TWILIO = 'twilio';
    const ULTRAMSG = 'ultramsg';

    public static function getOptions()
    {
        return [
            self::META => 'Meta',
            self::TWILIO => 'Twilio',
            self::ULTRAMSG => 'Ultramsg',
            self::GREENAPI => 'Green Api',
        ];
    }
}
