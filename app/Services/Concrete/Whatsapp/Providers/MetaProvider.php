<?php

namespace App\Services\Concrete\Whatsapp\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Services\Concrete\Whatsapp\Contracts\WhatsappProviderInterface;
use App\Services\Concrete\Whatsapp\DTO\WhatsappData;

class MetaProvider implements WhatsappProviderInterface
{
    protected $setting;

    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    public function send(WhatsappData $data): array
    {
        try {

            if ($data->attachment && file_exists($data->attachment)) {

                return $this->sendDocument($data);
            }

            return $this->sendText($data);
        } catch (Exception $e) {

            return [

                'status' => false,

                'message' => $e->getMessage()

            ];
        }
    }

    /**
     * Send Text
     */
    private function sendText($data)
    {

        $url = "https://graph.facebook.com/v23.0/{$this->setting->phone_number_id}/messages";

        $response = Http::withToken(
            $this->setting->access_token
        )->post($url, [

            "messaging_product" => "whatsapp",

            "to" => $this->formatPhone($data->phone),

            "type" => "text",

            "text" => [

                "body" => $data->message

            ]

        ]);

        if ($response->successful()) {

            return [

                'status' => true,

                'message' => 'Message sent.',

                'response' => $response->json()

            ];
        }

        return [

            'status' => false,

            'message' => $response->body()

        ];
    }

    /**
     * Send PDF
     */
    private function sendDocument($data)
    {

        $mediaId = $this->uploadMedia($data->attachment);

        if (!$mediaId['status']) {

            return $mediaId;
        }

        $url = "https://graph.facebook.com/v23.0/{$this->setting->phone_number_id}/messages";

        $response = Http::withToken(
            $this->setting->access_token
        )->post($url, [

            "messaging_product" => "whatsapp",

            "to" => $this->formatPhone($data->phone),

            "type" => "document",

            "document" => [

                "id" => $mediaId['media_id'],

                "caption" => $data->message,

                "filename" => $data->fileName
                    ?? basename($data->attachment)

            ]

        ]);

        if ($response->successful()) {

            return [

                'status' => true,

                'message' => 'Document sent.',

                'response' => $response->json()

            ];
        }

        return [

            'status' => false,

            'message' => $response->body()

        ];
    }

    /**
     * Upload Media
     */
    private function uploadMedia($file)
    {

        $url = "https://graph.facebook.com/v23.0/{$this->setting->phone_number_id}/media";

        $response = Http::withToken(
            $this->setting->access_token
        )
            ->attach(
                'file',
                fopen($file, 'r'),
                basename($file)
            )
            ->post($url, [

                'messaging_product' => 'whatsapp'

            ]);

        if (!$response->successful()) {

            return [

                'status' => false,

                'message' => $response->body()

            ];
        }

        return [

            'status' => true,

            'media_id' => $response['id']

        ];
    }

    /**
     * Phone Format
     */
    private function formatPhone($phone)
    {

        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 1) == '0') {

            $phone = '92' . substr($phone, 1);
        }

        return $phone;
    }
}
