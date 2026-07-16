<?php

namespace App\Services\Concrete\SMS\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Services\Concrete\SMS\DTO\SMSData;
use App\Services\Concrete\SMS\Contracts\SMSProviderInterface;

class MSG91Provider implements SMSProviderInterface
{
    protected $setting;

    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    public function send(SMSData $data): array
    {
        try {

            $response = Http::withHeaders([
                'authkey' => $this->setting->api_key,
                'accept' => 'application/json',
                'content-type' => 'application/json'
            ])->post(
                'https://control.msg91.com/api/v5/flow',
                [

                    'template_id' => $this->setting->template_id,

                    'flow_id' => $this->setting->flow_id,

                    'sender' => $this->setting->sender_id,

                    'recipients' => [

                        [

                            'mobiles' => $this->formatPhone($data->phone),

                            // Flow Variable
                            'MESSAGE' => $data->message

                        ]

                    ]

                ]
            );

            if ($response->successful()) {

                return [

                    'status' => true,

                    'message' => 'SMS sent successfully.',

                    'response' => $response->json()

                ];
            }

            return [

                'status' => false,

                'message' => $response->body(),

                'response' => $response->json()

            ];
        } catch (Exception $e) {

            return [

                'status' => false,

                'message' => $e->getMessage()

            ];
        }
    }

    private function formatPhone($phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 1) == '0') {

            $phone = '91' . substr($phone, 1);
        }

        return $phone;
    }
}
