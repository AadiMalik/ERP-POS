<?php

namespace App\Services\Concrete\Whatsapp\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Services\Concrete\Whatsapp\Contracts\WhatsappProviderInterface;
use App\Services\Concrete\Whatsapp\DTO\WhatsappData;

class TwilioProvider implements WhatsappProviderInterface
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
     * Send Text
     */
    private function sendText(WhatsappData $data): array
    {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->setting->instance_id}/Messages.json";

        $response = Http::asForm()
            ->withBasicAuth(
                $this->setting->instance_id,
                $this->setting->access_token
            )
            ->post($url, [

                'From' => 'whatsapp:' . $this->setting->phone_number_id,

                'To' => 'whatsapp:' . $this->formatPhone($data->phone),

                'Body' => $data->message

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
     * Send PDF
     */
    private function sendDocument(WhatsappData $data): array
    {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->setting->instance_id}/Messages.json";

        $response = Http::asForm()
            ->withBasicAuth(
                $this->setting->instance_id,
                $this->setting->access_token
            )
            ->post($url, [

                'From' => 'whatsapp:' . $this->setting->phone_number_id,

                'To' => 'whatsapp:' . $this->formatPhone($data->phone),

                'Body' => $data->message,

                // PUBLIC URL
                'MediaUrl' => $data->attachment

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

    private function formatPhone($phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 1) == '0') {

            $phone = '92' . substr($phone, 1);
        }

        return $phone;
    }
}
