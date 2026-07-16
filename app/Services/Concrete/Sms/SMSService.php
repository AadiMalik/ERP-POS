<?php

namespace App\Services\Concrete\SMS;

use Exception;
use App\Models\SmsSetting;
use App\Services\Concrete\SMS\DTO\SMSData;

class SMSService
{
    public function send(string $businessId, SMSData $sms): array
    {
        try {

            $setting = SmsSetting::where('business_id', $businessId)->first();

            if (!$setting) {
                return [
                    'status' => false,
                    'message' => 'SMS settings not found.'
                ];
            }

            if (!$setting->enable_sms) {
                return [
                    'status' => false,
                    'message' => 'SMS service disabled.'
                ];
            }

            switch ($setting->provider) {

                case 'twilio':

                    if (
                        empty($setting->username) ||
                        empty($setting->password) ||
                        empty($setting->sender_id)
                    ) {
                        return [
                            'status' => false,
                            'message' => 'Twilio configuration incomplete.'
                        ];
                    }

                    break;

                case 'brandsms':

                    if (
                        empty($setting->username) ||
                        empty($setting->password) ||
                        empty($setting->sender_id)
                    ) {
                        return [
                            'status' => false,
                            'message' => 'BrandSMS configuration incomplete.'
                        ];
                    }

                    break;

                case 'msg91':

                    if (
                        empty($setting->api_key) ||
                        empty($setting->sender_id)
                    ) {
                        return [
                            'status' => false,
                            'message' => 'MSG91 configuration incomplete.'
                        ];
                    }

                    break;

                case 'jazz':

                    if (
                        empty($setting->username) ||
                        empty($setting->password)
                    ) {
                        return [
                            'status' => false,
                            'message' => 'Jazz configuration incomplete.'
                        ];
                    }

                    break;
            }

            $provider = SMSFactory::make($setting);

            return $provider->send($sms);

        } catch (Exception $e) {

            return [
                'status' => false,
                'message' => $e->getMessage()
            ];

        }
    }
}