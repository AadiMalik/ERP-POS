<?php

namespace App\Services\Concrete\SMS\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Services\Concrete\SMS\DTO\SMSData;
use App\Services\Concrete\SMS\Contracts\SMSProviderInterface;

class VonageProvider implements SMSProviderInterface
{
    protected $setting;

    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    public function send(SMSData $data): array
    {
        try {

            $response = Http::asForm()->post(
                'https://rest.nexmo.com/sms/json',
                [

                    'api_key'    => $this->setting->api_key,

                    'api_secret' => $this->setting->password,

                    'from'       => $this->setting->sender_id,

                    'to'         => $this->formatPhone($data->phone),

                    'text'       => $data->message

                ]
            );

            if (!$response->successful()) {

                return [

                    'status' => false,

                    'message' => $response->body()

                ];
            }

            $result = $response->json();

            if (
                isset($result['messages'][0]['status']) &&
                $result['messages'][0]['status'] == "0"
            ) {

                return [

                    'status' => true,

                    'message' => 'SMS sent successfully.',

                    'response' => $result

                ];
            }

            return [

                'status' => false,

                'message' => $result['messages'][0]['error-text']
                    ?? 'Unable to send SMS.',

                'response' => $result

            ];
        } catch (Exception $e) {

            return [

                'status' => false,

                'message' => $e->getMessage()

            ];
        }
    }

    /**
     * Convert local phone to E.164 format.
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
