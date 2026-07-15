<?php

namespace App\Services\Concrete\Whatsapp;

use Exception;

use App\Services\Concrete\Whatsapp\Providers\MetaProvider;
use App\Services\Concrete\Whatsapp\Providers\TwilioProvider;
use App\Services\Concrete\Whatsapp\Providers\UltraMsgProvider;
use App\Services\Concrete\Whatsapp\Providers\GreenApiProvider;

class WhatsappFactory
{

    public static function make(object $setting)
    {

        switch ($setting->provider) {

            case 'meta':

                return new MetaProvider($setting);

            case 'twilio':

                return new TwilioProvider($setting);

            case 'ultramsg':

                return new UltraMsgProvider($setting);

            case 'greenapi':

                return new GreenApiProvider($setting);

            default:

                throw new Exception("Unsupported WhatsApp provider.");
        }

    }

}