<?php

namespace App\Services\Concrete\Whatsapp;

use Exception;
use App\Models\WhatsappSetting;
use App\Services\Concrete\Whatsapp\DTO\WhatsappData;

class WhatsappService
{

    public function send(
        string $businessId,
        WhatsappData $data
    ): array
    {

        try {

            $setting = WhatsappSetting::where(
                'business_id',
                $businessId
            )->first();

            if (!$setting) {

                return [
                    'status' => false,
                    'message' => 'Whatsapp setting not found.'
                ];

            }

            if (!$setting->enable_whatsapp) {

                return [
                    'status' => false,
                    'message' => 'Whatsapp disabled.'
                ];

            }

            switch ($setting->provider) {

                case 'greenapi':

                    if (
                        empty($setting->instance_id) ||
                        empty($setting->api_key)
                    ) {

                        return [
                            'status' => false,
                            'message' => 'Green API configuration incomplete.'
                        ];

                    }

                    break;

                case 'ultramsg':

                    if (
                        empty($setting->instance_id) ||
                        empty($setting->api_key)
                    ) {

                        return [
                            'status' => false,
                            'message' => 'UltraMsg configuration incomplete.'
                        ];

                    }

                    break;

                case 'meta':

                    if (
                        empty($setting->access_token) ||
                        empty($setting->phone_number_id)
                    ) {

                        return [
                            'status' => false,
                            'message' => 'Meta configuration incomplete.'
                        ];

                    }

                    break;

                case 'twilio':

                    if (
                        empty($setting->instance_id) ||
                        empty($setting->access_token) ||
                        empty($setting->api_key)
                    ) {

                        return [
                            'status' => false,
                            'message' => 'Twilio configuration incomplete.'
                        ];

                    }

                    break;

                default:

                    return [
                        'status' => false,
                        'message' => 'Invalid provider.'
                    ];

            }

            $provider = WhatsappFactory::make($setting);

            return $provider->send($data);

        }

        catch (Exception $e) {

            return [

                'status'=>false,

                'message'=>$e->getMessage()

            ];

        }

    }

}