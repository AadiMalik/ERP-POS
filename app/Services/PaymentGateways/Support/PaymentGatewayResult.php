<?php

namespace App\Services\PaymentGateways\Support;

/**
 * Common result shape returned by every PaymentGatewayProviderContract method
 * that reports a payment/refund/cancel state (verify/handleWebhook/refund/
 * cancel) - one shape instead of near-duplicate DTOs per operation.
 * Plain (non-readonly) promoted properties - this codebase targets PHP 8.0.2
 * (composer.json), and readonly properties require PHP 8.1+.
 */
final class PaymentGatewayResult
{
    public function __construct(
        public string $status,
        public ?string $gatewayTransactionId = null,
        public ?string $gatewayReference = null,
        public ?string $internalReference = null,
        public ?float $amount = null,
        public ?string $currency = null,
        public ?string $failureCode = null,
        public ?string $failureReason = null,
        public ?string $eventId = null,
        public array $meta = [],
    ) {
    }
}
