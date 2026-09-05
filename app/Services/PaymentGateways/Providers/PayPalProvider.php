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
 * PayPal Checkout Orders v2 REST API - official, stable, extensively
 * documented (developer.paypal.com/docs/api/orders/v2). OAuth2 client-
 * credentials flow, hosted "approve" redirect, explicit capture, and
 * PayPal's own /v1/notifications/verify-webhook-signature endpoint for
 * webhook authenticity (no local HMAC - PayPal verifies its own signature
 * for you). Unverified against a live PayPal sandbox - no test credentials
 * were available at implementation time, but this integration follows
 * PayPal's official, versioned API directly.
 */
class PayPalProvider implements PaymentGatewayProviderContract, PaymentGatewayConnectionTestable
{
    private const SANDBOX_API = 'https://api-m.sandbox.paypal.com';
    private const LIVE_API = 'https://api-m.paypal.com';

    public function initiate(PaymentGateway $gateway, Order $order, PaymentTransaction $transaction): array
    {
        $config = $gateway->activeConfig();
        $token = $this->accessToken($gateway);

        $response = Http::timeout(15)->withToken($token)->post($this->apiBase($gateway) . '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $transaction->internal_reference,
                'custom_id' => $transaction->internal_reference,
                'amount' => [
                    'currency_code' => strtoupper($transaction->currency),
                    'value' => number_format($transaction->amount, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => $config['return_url'] ?? '',
                'cancel_url' => $config['cancel_url'] ?? ($config['return_url'] ?? ''),
            ],
        ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('PayPal order creation failed: ' . $this->errorMessage($response));
        }

        $order_body = $response->json();
        $approveLink = collect($order_body['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        return [
            'redirect_url' => $approveLink,
            'gateway_transaction_id' => $order_body['id'] ?? null,
            'gateway_reference' => $order_body['id'] ?? null,
        ];
    }

    public function verify(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult
    {
        $orderId = $transaction->gateway_transaction_id ?: $transaction->gateway_reference;

        if (!$orderId) {
            return new PaymentGatewayResult(status: 'unknown', internalReference: $transaction->internal_reference);
        }

        $token = $this->accessToken($gateway);
        $response = Http::timeout(15)->withToken($token)->get($this->apiBase($gateway) . "/v2/checkout/orders/{$orderId}");

        if ($response->failed()) {
            return new PaymentGatewayResult(status: 'unknown', internalReference: $transaction->internal_reference);
        }

        $order_body = $response->json();

        // The buyer has approved but the funds aren't captured yet - PayPal
        // requires an explicit capture call before it's actually paid.
        if (($order_body['status'] ?? null) === 'APPROVED') {
            $captureResponse = Http::timeout(15)->withToken($token)
                ->post($this->apiBase($gateway) . "/v2/checkout/orders/{$orderId}/capture");

            if ($captureResponse->successful()) {
                $order_body = $captureResponse->json();
            }
        }

        return $this->mapOrder($order_body);
    }

    public function handleWebhook(PaymentGateway $gateway, Request $request): PaymentGatewayResult
    {
        $config = $gateway->activeConfig();
        $payload = $request->getContent();

        if (!$this->verifyWebhookSignature($gateway, $request, $payload)) {
            throw new InvalidWebhookSignatureException('PayPal webhook signature verification failed.');
        }

        $event = json_decode($payload, true) ?? [];
        $resource = $event['resource'] ?? [];
        $type = $event['event_type'] ?? '';

        $status = match (true) {
            $type === 'PAYMENT.CAPTURE.COMPLETED' => 'paid',
            $type === 'PAYMENT.CAPTURE.DENIED' => 'failed',
            $type === 'PAYMENT.CAPTURE.REFUNDED' => 'refunded',
            $type === 'CHECKOUT.ORDER.APPROVED' => 'authorized',
            default => 'unknown',
        };

        return new PaymentGatewayResult(
            status: $status,
            gatewayTransactionId: $resource['id'] ?? null,
            internalReference: $resource['custom_id'] ?? null,
            amount: isset($resource['amount']['value']) ? (float) $resource['amount']['value'] : null,
            currency: $resource['amount']['currency_code'] ?? null,
            eventId: $event['id'] ?? sha1($payload),
            meta: $resource,
        );
    }

    public function refund(PaymentGateway $gateway, PaymentTransaction $transaction, float $amount): PaymentGatewayResult
    {
        $captureId = $transaction->gateway_transaction_id;

        if (!$captureId) {
            throw new PaymentGatewayException('No PayPal capture id recorded for this transaction - cannot refund.');
        }

        $token = $this->accessToken($gateway);
        $response = Http::timeout(15)->withToken($token)
            ->post($this->apiBase($gateway) . "/v2/payments/captures/{$captureId}/refund", [
                'amount' => [
                    'value' => number_format($amount, 2, '.', ''),
                    'currency_code' => strtoupper($transaction->currency),
                ],
            ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('PayPal refund failed: ' . $this->errorMessage($response));
        }

        $refund = $response->json();

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
        // No explicit "void order" endpoint before capture - an uncaptured
        // PayPal order simply expires on its own.
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

    /** Safe, side-effect-free credential check: just an OAuth2 token request. */
    public function testConnection(PaymentGateway $gateway): array
    {
        try {
            $this->accessToken($gateway);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Could not authenticate with PayPal: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'PayPal credentials are valid.'];
    }

    private function mapOrder(array $order): PaymentGatewayResult
    {
        $capture = $order['purchase_units'][0]['payments']['captures'][0] ?? null;

        $status = match ($order['status'] ?? null) {
            'COMPLETED' => 'paid',
            'APPROVED' => 'authorized',
            'VOIDED' => 'cancelled',
            'CREATED', 'PAYER_ACTION_REQUIRED' => 'pending',
            default => 'unknown',
        };

        return new PaymentGatewayResult(
            status: $status,
            gatewayTransactionId: $capture['id'] ?? ($order['id'] ?? null),
            internalReference: $order['purchase_units'][0]['reference_id'] ?? null,
            amount: isset($capture['amount']['value']) ? (float) $capture['amount']['value'] : null,
            currency: $capture['amount']['currency_code'] ?? null,
            meta: $order,
        );
    }

    /**
     * PayPal verifies its own webhook signature for you - no local HMAC.
     * See developer.paypal.com/api/rest/webhooks/#verify-webhook-signature.
     */
    private function verifyWebhookSignature(PaymentGateway $gateway, Request $request, string $payload): bool
    {
        $config = $gateway->activeConfig();
        $token = $this->accessToken($gateway);

        $response = Http::timeout(15)->withToken($token)->post($this->apiBase($gateway) . '/v1/notifications/verify-webhook-signature', [
            'transmission_id' => $request->header('Paypal-Transmission-Id'),
            'transmission_time' => $request->header('Paypal-Transmission-Time'),
            'cert_url' => $request->header('Paypal-Cert-Url'),
            'auth_algo' => $request->header('Paypal-Auth-Algo'),
            'transmission_sig' => $request->header('Paypal-Transmission-Sig'),
            'webhook_id' => $config['webhook_id'] ?? '',
            'webhook_event' => json_decode($payload, true),
        ]);

        return $response->successful() && $response->json('verification_status') === 'SUCCESS';
    }

    private function accessToken(PaymentGateway $gateway): string
    {
        $config = $gateway->activeConfig();

        $response = Http::timeout(15)->asForm()
            ->withBasicAuth($config['client_id'] ?? '', $config['client_secret'] ?? '')
            ->post($this->apiBase($gateway) . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if ($response->failed()) {
            throw new PaymentGatewayException('PayPal authentication failed: ' . $this->errorMessage($response));
        }

        return $response->json('access_token');
    }

    private function apiBase(PaymentGateway $gateway): string
    {
        return $gateway->active_mode === 'live' ? self::LIVE_API : self::SANDBOX_API;
    }

    private function errorMessage($response): string
    {
        return $response->json('message') ?? ('HTTP ' . $response->status());
    }
}
