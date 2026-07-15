<?php

namespace App\Services\Concrete\Whatsapp\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Services\Concrete\Whatsapp\DTO\WhatsappData;
use App\Services\Concrete\Whatsapp\Contracts\WhatsappProviderInterface;

class GreenApiProvider implements WhatsappProviderInterface
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

                return $this->sendFile($data);
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
     * Send Text Message
     */
    private function sendText(WhatsappData $data): array
    {
        $url = "https://7105.api.greenapi.com/waInstance{$this->setting->instance_id}/sendMessage/{$this->setting->api_key}";

        $response = Http::post($url, [

            "chatId" => $this->formatPhone($data->phone),

            "message" => $data->message

        ]);

        if ($response->successful()) {

            return [

                'status' => true,

                'message' => 'Whatsapp sent successfully.',

                'response' => $response->json()

            ];
        }

        return [

            'status' => false,

            'message' => $response->body()

        ];
    }

    /**
     * Send Document
     */
    private function sendFile(WhatsappData $data): array
    {

        $upload = $this->uploadFile($data->attachment);

        if (!$upload['status']) {

            return $upload;

        }

        $url = "https://7105.api.greenapi.com/waInstance{$this->setting->instance_id}/sendFileByUpload/{$this->setting->api_key}";

        $response = Http::post($url, [

            "chatId" => $this->formatPhone($data->phone),

            "urlFile" => $upload['url'],

            "fileName" => $data->fileName ?? basename($data->attachment),

            "caption" => $data->message

        ]);

        if ($response->successful()) {

            return [

                'status' => true,

                'message' => 'Whatsapp document sent successfully.',

                'response' => $response->json()

            ];
        }

        return [

            'status' => false,

            'message' => $response->body()

        ];

    }

    /**
     * Upload File
     */
    private function uploadFile($file): array
    {

        $url = "https://7105.api.greenapi.com/waInstance{$this->setting->instance_id}/uploadFile/{$this->setting->api_key}";

        $response = Http::attach(

            'file',

            fopen($file, 'r'),

            basename($file)

        )->post($url);

        if (!$response->successful()) {

            return [

                'status' => false,

                'message' => 'File upload failed.'

            ];

        }

        return [

            'status' => true,

            'url' => $response['urlFile']

        ];

    }

    /**
     * Format Phone
     */
    private function formatPhone($phone)
    {

        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 1) == '0') {

            $phone = '92' . substr($phone, 1);

        }

        return $phone . '@c.us';
    }
}