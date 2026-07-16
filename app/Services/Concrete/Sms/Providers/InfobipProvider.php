<?php

namespace App\Services\Concrete\SMS\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Services\Concrete\SMS\DTO\SMSData;
use App\Services\Concrete\SMS\Contracts\SMSProviderInterface;

class InfobipProvider implements SMSProviderInterface
{
    protected $setting;

    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    public function send(SMSData $data): array
    {
        try {

            $url = rtrim($this->setting->base_url, '/')
                . '/sms/2/text/advanced';

            $response = Http::withHeaders([
                'Authorization' => 'App ' . $this->setting->api_key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($url, [

                'messages' => [[

                    'from' => $this->setting->sender_id,

                    'destinations' => [[

                        'to' => $this->formatPhone($data->phone)

                    ]],

                    'text' => $data->message

                ]]

            ]);

            if ($response->successful()) {

                return [
                    'status'   => true,
                    'message'  => 'SMS sent successfully.',
                    'response' => $response->json()
                ];
            }

            return [
                'status'   => false,
                'message'  => $response->body(),
                'response' => $response->json()
            ];
        } catch (Exception $e) {

            return [
                'status'  => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Convert local phone to international format.
     */
    private function formatPhone($phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 1) == '0') {

            $phone = '92' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '+')) {

            $phone = '+' . $phone;
        }

        return $phone;
    }
}
