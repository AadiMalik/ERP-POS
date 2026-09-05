<?php

namespace App\Services\PaymentGateways\Providers;

class NayaPayProvider extends StubProvider
{
    protected function providerLabel(): string
    {
        return 'NayaPay';
    }

    public function supportsWebhook(): bool
    {
        return true;
    }
}
