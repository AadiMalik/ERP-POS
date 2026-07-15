<?php

namespace App\Services\Concrete\Whatsapp\DTO;

class WhatsappData
{
    public string $phone;

    public string $message;

    public ?string $attachment;

    public ?string $fileName;

    public function __construct(array $data)
    {
        $this->phone = $data['phone'];

        $this->message = $data['message'];

        $this->attachment = $data['attachment'] ?? null;

        $this->fileName = $data['file_name'] ?? null;
    }
}