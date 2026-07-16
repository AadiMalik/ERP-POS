<?php

namespace App\Services\Concrete\SMS\Contracts;

use App\Services\Concrete\SMS\DTO\SMSData;

interface SMSProviderInterface
{
    public function send(SMSData $data): array;
}