<?php

namespace App\Services\Concrete\Whatsapp\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Services\Concrete\Whatsapp\Contracts\WhatsappProviderInterface;
use App\Services\Concrete\Whatsapp\DTO\WhatsappData;

class UltraMsgProvider implements WhatsappProviderInterface
{
    protected $setting;

    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    public function send(WhatsappData $data): array
    {
        try {

            if (!empty($data->attachment)) {
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
     * Send Text Message
     */
    private function sendText(WhatsappData $data): array
    {
        $url = "https://api.ultramsg.com/{$this->setting->instance_id}/messages/chat";

        $response = Http::asForm()->post($url, [

            'token' => $this->setting->api_key,

            'to' => $this->formatPhone($data->phone),

            'body' => $data->message

        ]);

        if ($response->successful()) {

            return [
                'status' => true,
                'message' => 'Message sent successfully.',
                'response' => $response->json()
            ];
        }

        return [
            'status' => false,
            'message' => $response->body()
        ];
    }

    /**
     * Send PDF / Document
     */
    private function sendDocument(WhatsappData $data): array
    {
        $url = "https://api.ultramsg.com/{$this->setting->instance_id}/messages/document";

        $response = Http::asForm()->post($url, [

            'token' => $this->setting->api_key,

            'to' => $this->formatPhone($data->phone),

            // Public URL
            'document' => $data->attachment,

            'filename' => $data->fileName
                ?? basename($data->attachment),

            'caption' => $data->message

        ]);

        if ($response->successful()) {

            return [
                'status' => true,
                'message' => 'Document sent successfully.',
                'response' => $response->json()
            ];
        }

        return [
            'status' => false,
            'message' => $response->body()
        ];
    }

    /**
     * Phone Format
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
