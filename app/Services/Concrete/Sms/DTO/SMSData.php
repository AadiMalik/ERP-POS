<?php

namespace App\Services\Concrete\SMS\DTO;

class SMSData
{
    public string $phone;
    public string $message;

    public function __construct(array $data)
    {
        $this->phone = $data['phone'];
        $this->message = $data['message'];
    }
}