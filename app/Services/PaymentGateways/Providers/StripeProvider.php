<?php

namespace App\Services\PaymentGateways\Providers;

use App\Contracts\PaymentGatewayConnectionTestable;
use App\Contracts\PaymentGatewayProviderContract;
use App\Exceptions\PaymentGateways\InvalidWebhookSignatureException;
use App\Exceptions\PaymentGateways\PaymentGatewayException;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Services\PaymentGateways\Support\PaymentGatewayResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Stripe integration via Stripe's plain REST API (Illuminate\Http, already a
 * project dependency via guzzlehttp/guzzle) rather than the stripe-php SDK,
 * so no new Composer dependency is introduced. Coded against Stripe's public
 * API docs (PaymentIntents + webhook signature scheme); unverified against a
 * live Stripe sandbox - no test credentials were available at implementation
 * time (see resources/docs/developer's payment gateway framework doc).
 *
 * Flow: initiate() creates a PaymentIntent and hands the frontend its
 * client_secret (Stripe.js/Payment Element confirms the payment client-side)
 * - verify() then re-checks the PaymentIntent status directly with Stripe
 * before anything is ever marked paid server-side.
 */
class StripeProvider implements PaymentGatewayProviderContract, PaymentGatewayConnectionTestable
{
    private const API_BASE = 'https://api.stripe.com/v1';

    // Currencies Stripe treats as having no minor unit (amount is NOT multiplied by 100).
    private const ZERO_DECIMAL_CURRENCIES = ['JPY', 'KRW', 'VND', 'CLP', 'BIF', 'DJF', 'GNF', 'ISK', 'KMF', 'PYG', 'RWF', 'UGX', 'VUV', 'XAF', 'XOF', 'XPF'];

    public function initiate(PaymentGateway $gateway, Order $order, PaymentTransaction $transaction): array
    {
        $config = $gateway->activeConfig();

        $response = $this->client($config)->asForm()->post(self::API_BASE . '/payment_intents', [
            'amount' => $this->toMinorUnits($transaction->amount, $transaction->currency),
            'currency' => strtolower($transaction->currency),
            'automatic_payment_methods[enabled]' => 'true',
            'metadata[order_id]' => $order->order_id,
            'metadata[internal_reference]' => $transaction->internal_reference,
        ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('Stripe payment intent creation failed: ' . $this->errorMessage($response));
        }

        $intent = $response->json();

        return [
            'client_secret' => $intent['client_secret'] ?? null,
            'publishable_key' => $config['publishable_key'] ?? null,
            'gateway_transaction_id' => $intent['id'] ?? null,
            'gateway_reference' => $intent['id'] ?? null,
        ];
    }

    public function verify(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult
    {
        $config = $gateway->activeConfig();
        $intentId = $transaction->gateway_transaction_id ?: $transaction->gateway_reference;

        if (!$intentId) {
            return new PaymentGatewayResult(status: 'unknown', internalReference: $transaction->internal_reference);
        }

        $response = $this->client($config)->get(self::API_BASE . '/payment_intents/' . $intentId);

        if ($response->failed()) {
            return new PaymentGatewayResult(status: 'unknown', internalReference: $transaction->internal_reference);
        }

        return $this->mapIntent($response->json());
    }

    public function handleWebhook(PaymentGateway $gateway, Request $request): PaymentGatewayResult
    {
        $config = $gateway->activeConfig();
        $payload = $request->getContent();
        $signatureHeader = $request->header('Stripe-Signature', '');

        $this->verifySignature($payload, $signatureHeader, $config['webhook_secret'] ?? '');

        $event = json_decode($payload, true);
        $object = $event['data']['object'] ?? [];
        $type = $event['type'] ?? '';

        $status = match (true) {
            $type === 'payment_intent.succeeded' => 'paid',
            $type === 'payment_intent.payment_failed' => 'failed',
            $type === 'payment_intent.canceled' => 'cancelled',
            $type === 'charge.refunded' => ($object['amount_refunded'] ?? 0) >= ($object['amount'] ?? 0) ? 'refunded' : 'partially_refunded',
            default => 'unknown',
        };

        return new PaymentGatewayResult(
            status: $status,
            gatewayTransactionId: $object['payment_intent'] ?? $object['id'] ?? null,
            internalReference: $object['metadata']['internal_reference'] ?? null,
            amount: isset($object['amount']) ? $this->fromMinorUnits($object['amount'], $object['currency'] ?? 'USD') : null,
            currency: isset($object['currency']) ? strtoupper($object['currency']) : null,
            failureCode: $status === 'failed' ? ($object['last_payment_error']['code'] ?? null) : null,
            failureReason: $status === 'failed' ? ($object['last_payment_error']['message'] ?? 'Stripe payment failed') : null,
            eventId: $event['id'] ?? null,
            meta: $object,
        );
    }

    public function refund(PaymentGateway $gateway, PaymentTransaction $transaction, float $amount): PaymentGatewayResult
    {
        $config = $gateway->activeConfig();
        $intentId = $transaction->gateway_transaction_id ?: $transaction->gateway_reference;

        $response = $this->client($config)->asForm()->post(self::API_BASE . '/refunds', [
            'payment_intent' => $intentId,
            'amount' => $this->toMinorUnits($amount, $transaction->currency),
        ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('Stripe refund failed: ' . $this->errorMessage($response));
        }

        $refund = $response->json();

        // 'refunded' here means "this refund operation succeeded" - whether
        // the ORIGINAL transaction ends up fully or partially refunded is a
        // cumulative decision the caller (PaymentTransactionService) makes.
        return new PaymentGatewayResult(
            status: 'refunded',
            gatewayTransactionId: $refund['id'] ?? null,
            internalReference: $transaction->internal_reference,
            amount: $amount,
            currency: $transaction->currency,
            meta: $refund,
        );
    }

    public function cancel(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult
    {
        $config = $gateway->activeConfig();
        $intentId = $transaction->gateway_transaction_id ?: $transaction->gateway_reference;

        $response = $this->client($config)->post(self::API_BASE . '/payment_intents/' . $intentId . '/cancel');

        if ($response->failed()) {
            throw new PaymentGatewayException('Stripe payment intent cancel failed: ' . $this->errorMessage($response));
        }

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

    /** Safe, side-effect-free credential check via Stripe's balance endpoint. */
    public function testConnection(PaymentGateway $gateway): array
    {
        $config = $gateway->activeConfig();

        try {
            $response = $this->client($config)->get(self::API_BASE . '/balance');
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Could not reach Stripe: ' . $e->getMessage()];
        }

        return $response->successful()
            ? ['success' => true, 'message' => 'Stripe credentials are valid.']
            : ['success' => false, 'message' => 'Stripe rejected the credentials: ' . $this->errorMessage($response)];
    }

    private function client(array $config)
    {
        return Http::timeout(15)->withToken($config['secret_key'] ?? '');
    }

    private function mapIntent(array $intent): PaymentGatewayResult
    {
        $status = match ($intent['status'] ?? null) {
            'succeeded' => 'paid',
            'processing' => 'processing',
            'requires_capture' => 'authorized',
            'canceled' => 'cancelled',
            'requires_payment_method', 'requires_confirmation', 'requires_action' => 'pending',
            default => 'unknown',
        };

        return new PaymentGatewayResult(
            status: $status,
            gatewayTransactionId: $intent['id'] ?? null,
            internalReference: $intent['metadata']['internal_reference'] ?? null,
            amount: isset($intent['amount']) ? $this->fromMinorUnits($intent['amount'], $intent['currency'] ?? 'usd') : null,
            currency: isset($intent['currency']) ? strtoupper($intent['currency']) : null,
            failureCode: $intent['last_payment_error']['code'] ?? null,
            failureReason: $intent['last_payment_error']['message'] ?? null,
            meta: $intent,
        );
    }

    /**
     * Stripe's webhook signature scheme: header is "t=<timestamp>,v1=<hash>[,v0=...]".
     * Expected hash = HMAC-SHA256("{timestamp}.{raw_body}", webhook_secret).
     * A 5-minute timestamp tolerance blocks replay of an old, still-valid signature.
     */
    private function verifySignature(string $payload, string $signatureHeader, string $secret): void
    {
        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
            $parts[$key][] = $value;
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (!$timestamp || empty($signatures) || !$secret) {
            throw new InvalidWebhookSignatureException('Stripe webhook signature header is missing or malformed.');
        }

        if (abs(time() - (int) $timestamp) > 300) {
            throw new InvalidWebhookSignatureException('Stripe webhook signature timestamp is outside tolerance.');
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, (string) $signature)) {
                return;
            }
        }

        throw new InvalidWebhookSignatureException('Stripe webhook signature verification failed.');
    }

    private function toMinorUnits(float $amount, string $currency): int
    {
        return in_array(strtoupper($currency), self::ZERO_DECIMAL_CURRENCIES, true)
            ? (int) round($amount)
            : (int) round($amount * 100);
    }

    private function fromMinorUnits(int $amount, string $currency): float
    {
        return in_array(strtoupper($currency), self::ZERO_DECIMAL_CURRENCIES, true)
            ? (float) $amount
            : $amount / 100;
    }

    private function errorMessage($response): string
    {
        return $response->json('error.message') ?? ('HTTP ' . $response->status());
    }
}
