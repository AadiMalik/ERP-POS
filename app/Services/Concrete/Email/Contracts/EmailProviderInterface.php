<?php

namespace App\Services\Concrete\Email\Contracts;

use App\Services\Concrete\Email\DTO\EmailData;

interface EmailProviderInterface
{
    public function send(
        EmailData $email
    ): bool;
}
