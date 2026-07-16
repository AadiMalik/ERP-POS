<?php

namespace App\Services\Concrete\SMS\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Services\Concrete\SMS\DTO\SMSData;
use App\Services\Concrete\SMS\Contracts\SMSProviderInterface;

class BrandSMSProvider implements SMSProviderInterface
{
    protected $setting;

    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    public function send(SMSData $data): array
    {
        try {

            $url = rtrim($this->setting->base_url, '/');

            $response = Http::asForm()->post($url, [

                'username' => $this->setting->username,

                'password' => $this->setting->password,

                'from' => $this->setting->sender_id,

                'to' => $this->formatPhone($data->phone),

                'text' => $data->message

            ]);

            if ($response->successful()) {

                return [

                    'status' => true,

                    'message' => 'SMS sent successfully.',

                    'response' => $response->body()

                ];

            }

            return [

                'status' => false,

                'message' => $response->body()

            ];

        } catch (Exception $e) {

            return [

                'status' => false,

                'message' => $e->getMessage()

            ];

        }
    }

    /**
     * Convert phone to international format.
     */
    private function formatPhone($phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 1) == '0') {

            $phone = '92' . substr($phone, 1);

        }

        return $phone;
    }
}