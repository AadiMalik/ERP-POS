<?php

namespace App\Services\Concrete\Whatsapp\Contracts;

use App\Services\Concrete\Whatsapp\DTO\WhatsappData;

interface WhatsappProviderInterface
{
    public function send(
        WhatsappData $data
    ): array;
}