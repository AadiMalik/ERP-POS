<?php

namespace App\Services\PaymentGateways\Providers;

use App\Contracts\PaymentGatewayConnectionTestable;
use App\Contracts\PaymentGatewayProviderContract;
use App\Exceptions\PaymentGateways\InvalidWebhookSignatureException;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Services\PaymentGateways\Support\PaymentGatewayResult;
use Illuminate\Http\Request;

/**
 * Fully working fake gateway used only by the automated test suite (never
 * shown in the CMS - see PaymentGatewayProviderRegistry::forSelect()), since
 * no real provider has live sandbox credentials to test against. A test
 * drives outcomes via the transaction's `meta.simulate_status` (for verify())
 * or the webhook JSON body's `status` field (for handleWebhook()).
 *
 * Webhook payload shape: {"event_id":"...","internal_reference":"...",
 * "status":"paid","gateway_transaction_id":"...","amount":123.45,"currency":"PKR"}
 * signed via header X-Dummy-Signature = HMAC-SHA256(raw body, webhook_secret).
 */
class DummyTestProvider implements PaymentGatewayProviderContract, PaymentGatewayConnectionTestable
{
    public function testConnection(PaymentGateway $gateway): array
    {
        return ['success' => true, 'message' => 'Dummy gateway always connects.'];
    }

    public function initiate(PaymentGateway $gateway, Order $order, PaymentTransaction $transaction): array
    {
        return [
            'redirect_url' => 'https://dummy-gateway.test/checkout/' . $transaction->internal_reference,
            'gateway_reference' => $transaction->internal_reference,
        ];
    }

    public function verify(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult
    {
        $status = $transaction->meta['simulate_status'] ?? 'paid';

        return new PaymentGatewayResult(
            status: $status,
            gatewayTransactionId: 'DUMMY-TXN-' . substr($transaction->payment_transaction_id, 0, 8),
            internalReference: $transaction->internal_reference,
            amount: (float) $transaction->amount,
            currency: $transaction->currency,
        );
    }

    public function handleWebhook(PaymentGateway $gateway, Request $request): PaymentGatewayResult
    {
        $config = $gateway->activeConfig();
        $payload = $request->getContent();
        $signature = $request->header('X-Dummy-Signature', '');
        $expected = hash_hmac('sha256', $payload, $config['webhook_secret'] ?? '');

        if (!hash_equals($expected, $signature)) {
            throw new InvalidWebhookSignatureException('Dummy gateway signature verification failed.');
        }

        $body = json_decode($payload, true) ?? [];

        return new PaymentGatewayResult(
            status: $body['status'] ?? 'unknown',
            gatewayTransactionId: $body['gateway_transaction_id'] ?? null,
            internalReference: $body['internal_reference'] ?? null,
            amount: isset($body['amount']) ? (float) $body['amount'] : null,
            currency: $body['currency'] ?? null,
            failureReason: $body['failure_reason'] ?? null,
            eventId: $body['event_id'] ?? null,
            meta: $body,
        );
    }

    public function refund(PaymentGateway $gateway, PaymentTransaction $transaction, float $amount): PaymentGatewayResult
    {
        // 'refunded' here means "this refund operation succeeded" - whether
        // the ORIGINAL transaction ends up fully or partially refunded is a
        // cumulative decision the caller (PaymentTransactionService) makes.
        return new PaymentGatewayResult(
            status: 'refunded',
            gatewayTransactionId: 'DUMMY-REFUND-' . substr($transaction->payment_transaction_id, 0, 8),
            internalReference: $transaction->internal_reference,
            amount: $amount,
            currency: $transaction->currency,
        );
    }

    public function cancel(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult
    {
        return new PaymentGatewayResult(status: 'cancelled', internalReference: $transaction->internal_reference);
    }

    public function supportsRefund(): bool
    {
        return true;
    }

    public function supportsWebhook(): bool
    {
        return true;
    }
}
