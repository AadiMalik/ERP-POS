<?php

namespace App\Exceptions\PaymentGateways;

/**
 * Thrown by a provider adapter's handleWebhook() when the request's signature
 * cannot be verified. The caller must log this as an 'invalid' webhook event
 * and never apply any state change from it.
 */
class InvalidWebhookSignatureException extends PaymentGatewayException
{
}
