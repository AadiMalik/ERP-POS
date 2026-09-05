<?php

namespace App\Services\PaymentGateways\Providers;

class SadaPayProvider extends StubProvider
{
    protected function providerLabel(): string
    {
        return 'SadaPay';
    }

    public function supportsWebhook(): bool
    {
        return true;
    }
}
