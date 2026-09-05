<?php

namespace App\Contracts;

use App\Models\PaymentGateway;

/**
 * Optional capability a provider adapter may implement when it has a safe,
 * real API call to verify credentials without side effects (e.g. Stripe's
 * GET /v1/balance). Kept separate from PaymentGatewayProviderContract so
 * providers without a safe ping endpoint (e.g. JazzCash) aren't forced to
 * fake one - PaymentGatewayService falls back to a config-completeness
 * check when a provider doesn't implement this.
 */
interface PaymentGatewayConnectionTestable
{
    /** @return array{success: bool, message: string} */
    public function testConnection(PaymentGateway $gateway): array;
}
