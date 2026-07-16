<?php

namespace App\Services\Concrete\SMS;

use Exception;
use App\Services\Concrete\SMS\Providers\TwilioProvider;
use App\Services\Concrete\SMS\Providers\BrandSMSProvider;
use App\Services\Concrete\SMS\Providers\InfobipProvider;
use App\Services\Concrete\SMS\Providers\MSG91Provider;
use App\Services\Concrete\SMS\Providers\VonageProvider;

class SMSFactory
{
    public static function make($setting)
    {
        return match ($setting->provider) {

            'twilio'   => new TwilioProvider($setting),

            'brandsms' => new BrandSMSProvider($setting),

            'infobip'  => new InfobipProvider($setting),

            'msg91'    => new MSG91Provider($setting),

            'vonage'   => new VonageProvider($setting),

            default => throw new Exception("Unsupported SMS Provider.")
        };
    }
}
