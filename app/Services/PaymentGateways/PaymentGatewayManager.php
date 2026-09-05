<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayProviderContract;
use App\Exceptions\PaymentGateways\PaymentGatewayException;
use App\Models\PaymentGateway;

class PaymentGatewayManager
{
    public function adapterFor(PaymentGateway $gateway): PaymentGatewayProviderContract
    {
        $meta = PaymentGatewayProviderRegistry::find($gateway->provider_code);

        if (!$meta) {
            throw new PaymentGatewayException("Unknown payment gateway provider: {$gateway->provider_code}");
        }

        return app($meta['adapter']);
    }
}
