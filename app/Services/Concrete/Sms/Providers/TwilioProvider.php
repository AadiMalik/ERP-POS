<?php

namespace App\Services\Concrete\SMS\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Services\Concrete\SMS\DTO\SMSData;
use App\Services\Concrete\SMS\Contracts\SMSProviderInterface;

class TwilioProvider implements SMSProviderInterface
{
    protected $setting;

    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    public function send(SMSData $data): array
    {
        try {

            $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->setting->username}/Messages.json";

            $response = Http::asForm()
                ->withBasicAuth(
                    $this->setting->username,   // Account SID
                    $this->setting->password    // Auth Token
                )
                ->post($url, [

                    'From' => $this->setting->sender_id,

                    'To' => $this->formatPhone($data->phone),

                    'Body' => $data->message

                ]);

            if ($response->successful()) {

                return [

                    'status' => true,

                    'message' => 'SMS sent successfully.',

                    'response' => $response->json()

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
     * Convert local phone to international format.
     */
    private function formatPhone($phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 1) == '0') {

            $phone = '92' . substr($phone, 1);
        }

        if (substr($phone, 0, 1) != '+') {

            $phone = '+' . $phone;
        }

        return $phone;
    }
}
