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
 * Safepay (getsafepay.com) - Pakistan's most modern, REST-documented local
 * gateway (docs.getsafepay.com): create an order/tracker session, redirect
 * the customer to Safepay's hosted "beacon" checkout, then verify via the
 * order-details endpoint or webhook. Unverified against a live Safepay
 * sandbox - no test credentials were available at implementation time;
 * confirm exact response field nesting against your own Safepay dashboard
 * docs before going live.
 */
class SafepayProvider implements PaymentGatewayProviderContract, PaymentGatewayConnectionTestable
{
    private const SANDBOX_API = 'https://sandbox.api.getsafepay.com';
    private const LIVE_API = 'https://api.getsafepay.com';
    private const SANDBOX_CHECKOUT = 'https://sandbox.getsafepay.com/checkout/';
    private const LIVE_CHECKOUT = 'https://getsafepay.com/checkout/';

    public function initiate(PaymentGateway $gateway, Order $order, PaymentTransaction $transaction): array
    {
        $config = $gateway->activeConfig();

        $response = $this->client($gateway)->post('/order/v1/init', [
            'client' => $config['api_key'] ?? '',
            'amount' => (int) round($transaction->amount * 100),
            'currency' => strtoupper($transaction->currency),
            'environment' => $gateway->active_mode,
            'source' => 'custom',
            'order_id' => $transaction->internal_reference,
        ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('Safepay order initiation failed: ' . $this->errorMessage($response));
        }

        $token = $response->json('data.token') ?? $response->json('token');

        if (!$token) {
            throw new PaymentGatewayException('Safepay did not return a checkout token.');
        }

        $redirectUrl = $this->checkoutBase($gateway) . '?beacon=' . $token
            . '&env=' . $gateway->active_mode
            . '&redirect_url=' . urlencode($config['redirect_url'] ?? '');

        return [
            'redirect_url' => $redirectUrl,
            'gateway_reference' => $token,
        ];
    }

    public function verify(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult
    {
        $token = $transaction->gateway_reference;

        if (!$token) {
            return new PaymentGatewayResult(status: 'unknown', internalReference: $transaction->internal_reference);
        }

        $response = $this->client($gateway)->get('/order/v1/details/' . $token);

        if ($response->failed()) {
            return new PaymentGatewayResult(status: 'unknown', internalReference: $transaction->internal_reference);
        }

        return $this->mapState($response->json() ?? [], $transaction->internal_reference);
    }

    public function handleWebhook(PaymentGateway $gateway, Request $request): PaymentGatewayResult
    {
        $config = $gateway->activeConfig();
        $payload = $request->getContent();
        $signature = $request->header('X-SFPY-Signature', '');
        $expected = hash_hmac('sha256', $payload, $config['webhook_secret'] ?? '');

        if (!hash_equals($expected, $signature)) {
            throw new InvalidWebhookSignatureException('Safepay X-SFPY-Signature verification failed.');
        }

        $event = json_decode($payload, true) ?? [];
        $data = $event['data'] ?? $event;
        $result = $this->mapState($data, $data['order_id'] ?? null);

        return new PaymentGatewayResult(
            status: $result->status,
            gatewayTransactionId: $result->gatewayTransactionId,
            internalReference: $data['order_id'] ?? null,
            eventId: $event['id'] ?? sha1($payload),
            meta: $data,
        );
    }

    public function refund(PaymentGateway $gateway, PaymentTransaction $transaction, float $amount): PaymentGatewayResult
    {
        $response = $this->client($gateway)->post('/refund/v1/late', [
            'tracker_token' => $transaction->gateway_reference,
            'amount' => (int) round($amount * 100),
        ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('Safepay refund failed: ' . $this->errorMessage($response));
        }

        $body = $response->json() ?? [];

        return new PaymentGatewayResult(
            status: 'refunded',
            gatewayTransactionId: $body['data']['token'] ?? null,
            internalReference: $transaction->internal_reference,
            amount: $amount,
            currency: $transaction->currency,
            meta: $body,
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

    /** Safe, side-effect-free credential check: a tiny init call with the real key. */
    public function testConnection(PaymentGateway $gateway): array
    {
        $config = $gateway->activeConfig();

        try {
            $response = $this->client($gateway)->post('/order/v1/init', [
                'client' => $config['api_key'] ?? '',
                'amount' => 100,
                'currency' => 'PKR',
                'environment' => $gateway->active_mode,
                'source' => 'custom',
            ]);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Could not reach Safepay: ' . $e->getMessage()];
        }

        return $response->successful()
            ? ['success' => true, 'message' => 'Safepay credentials are valid.']
            : ['success' => false, 'message' => 'Safepay rejected the credentials: ' . $this->errorMessage($response)];
    }

    private function client(PaymentGateway $gateway)
    {
        $config = $gateway->activeConfig();

        return Http::timeout(15)
            ->withToken($config['api_secret'] ?? '')
            ->baseUrl($gateway->active_mode === 'live' ? self::LIVE_API : self::SANDBOX_API);
    }

    private function checkoutBase(PaymentGateway $gateway): string
    {
        return $gateway->active_mode === 'live' ? self::LIVE_CHECKOUT : self::SANDBOX_CHECKOUT;
    }

    private function mapState(array $data, ?string $internalReference): PaymentGatewayResult
    {
        $inner = $data['data'] ?? $data;
        $state = $inner['state'] ?? null;

        $status = match ($state) {
            'PAYMENT_SUCCESS_COMPLETED', 'COMPLETED' => 'paid',
            'PAYMENT_INITIATED', 'TRACKER_CREATED' => 'pending',
            'PAYMENT_FAILED', 'FAILED' => 'failed',
            'PAYMENT_CANCELLED', 'CANCELLED' => 'cancelled',
            default => 'unknown',
        };

        return new PaymentGatewayResult(
            status: $status,
            gatewayTransactionId: $inner['token'] ?? null,
            internalReference: $internalReference,
            meta: $inner,
        );
    }

    private function errorMessage($response): string
    {
        return $response->json('message') ?? ('HTTP ' . $response->status());
    }
}
