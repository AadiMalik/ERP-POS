<?php

namespace App\Services\Concrete\SMS;

use Exception;
use App\Models\SmsSetting;
use App\Services\Concrete\SMS\DTO\SMSData;

class SMSService
{
    public function send(string $businessId, SMSData $sms): array
    {
        $setting = SmsSetting::where('business_id', $businessId)->first();

        if (!$setting) {
            return [
                'status' => false,
                'message' => 'SMS settings not found.'
            ];
        }

        return $this->deliver($setting, $sms);
    }

    /**
     * Sends using the platform-level channel config (the SmsSetting row
     * with business_id = NULL). See EmailService::sendPlatform() for why.
     */
    public function sendPlatform(SMSData $sms): array
    {
        $setting = SmsSetting::whereNull('business_id')->first();

        if (!$setting) {
            return [
                'status' => false,
                'message' => 'Platform SMS settings are not configured.'
            ];
        }

        return $this->deliver($setting, $sms);
    }

    protected function deliver(SmsSetting $setting, SMSData $sms): array
    {
        try {
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
