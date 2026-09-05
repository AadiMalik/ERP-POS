<?php

namespace App\Services\PaymentGateways\Providers;

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
 * PayFast Pakistan (payfast.com.pk) Internet Payment Gateway (IPG) - distinct
 * from the unrelated South African "PayFast". Coded against PayFast PK's
 * published REST "PostTransaction" hosted-checkout API. Unverified against a
 * live sandbox - no test credentials were available at implementation time,
 * and PayFast PK's public documentation has varied across versions, so
 * confirm exact field/endpoint names against your own merchant integration
 * pack before going live.
 *
 * No broadly-documented public status-inquiry API exists for this provider,
 * so verify() does not fabricate one - the IPN callback (handleWebhook()) is
 * this provider's authoritative channel, same rationale as EasypaisaProvider.
 */
class PayFastProvider implements PaymentGatewayProviderContract
{
    private const SANDBOX_URL = 'https://ipguat.apps.net.pk/Ecommerce/api/Transaction/PostTransaction';
    private const LIVE_URL = 'https://ipg1.apps.net.pk/Ecommerce/api/Transaction/PostTransaction';

    public function initiate(PaymentGateway $gateway, Order $order, PaymentTransaction $transaction): array
    {
        $config = $gateway->activeConfig();

        $response = Http::timeout(15)->post($this->baseUrl($gateway), [
            'MERCHANT_ID' => $config['merchant_id'] ?? '',
            'SECURED_KEY' => $config['secured_key'] ?? '',
            'STORE_ID' => $config['store_id'] ?? '',
            'TXNAMT' => number_format($transaction->amount, 2, '.', ''),
            'BASKET_ID' => $transaction->internal_reference,
            'CURRENCY_CODE' => $transaction->currency,
            'ORDER_DATE' => now()->format('Y-m-d H:i:s'),
            'TXNDESC' => 'Order ' . $order->order_id,
            'SUCCESS_URL' => $config['return_url'] ?? '',
            'FAILURE_URL' => $config['return_url'] ?? '',
            'CHECKOUT_URL' => $config['return_url'] ?? '',
            'CUSTOMER_EMAIL_ADDRESS' => optional($order->user)->email,
            'CUSTOMER_MOBILE_NO' => optional($order->user)->phone,
            'TRAN_TYPE' => 'ECOMM_PURCHASE_TRANSACTION',
            'PROCCODE' => '00',
        ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('PayFast transaction initiation failed: HTTP ' . $response->status());
        }

        $body = $response->json() ?? [];

        return [
            'redirect_url' => $body['CHECKOUT_URL'] ?? $body['REDIRECT_URL'] ?? null,
            'gateway_reference' => $transaction->internal_reference,
            'meta' => $body,
        ];
    }

    public function verify(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult
    {
        return new PaymentGatewayResult(status: 'pending', internalReference: $transaction->internal_reference);
    }

    public function handleWebhook(PaymentGateway $gateway, Request $request): PaymentGatewayResult
    {
        $config = $gateway->activeConfig();
        $payload = $request->all();

        $receivedHash = $payload['validation_hash'] ?? null;
        unset($payload['validation_hash']);

        $expectedHash = $this->computeHash($payload, $config['secured_key'] ?? '');

        if (!$receivedHash || !hash_equals($expectedHash, (string) $receivedHash)) {
            throw new InvalidWebhookSignatureException('PayFast validation_hash verification failed.');
        }

        // '000' is PayFast PK's documented success code.
        $status = ($payload['err_code'] ?? null) === '000' ? 'paid' : 'failed';

        return new PaymentGatewayResult(
            status: $status,
            gatewayTransactionId: $payload['transaction_id'] ?? null,
            internalReference: $payload['basket_id'] ?? null,
            amount: isset($payload['transaction_amount']) ? (float) $payload['transaction_amount'] : null,
            failureCode: $status === 'failed' ? ($payload['err_code'] ?? null) : null,
            failureReason: $status === 'failed' ? ($payload['err_msg'] ?? 'Unknown PayFast response') : null,
            eventId: ($payload['basket_id'] ?? '') . ':' . ($payload['transaction_id'] ?? ''),
            meta: $payload,
        );
    }

    public function refund(PaymentGateway $gateway, PaymentTransaction $transaction, float $amount): PaymentGatewayResult
    {
        $config = $gateway->activeConfig();

        $response = Http::timeout(15)->post($this->refundUrl($gateway), [
            'MERCHANT_ID' => $config['merchant_id'] ?? '',
            'SECURED_KEY' => $config['secured_key'] ?? '',
            'STORE_ID' => $config['store_id'] ?? '',
            'BASKET_ID' => $transaction->internal_reference,
            'TXNAMT' => number_format($amount, 2, '.', ''),
        ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('PayFast refund failed: HTTP ' . $response->status());
        }

        $body = $response->json() ?? [];

        return new PaymentGatewayResult(
            status: 'refunded',
            gatewayTransactionId: $body['refund_transaction_id'] ?? null,
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

    private function baseUrl(PaymentGateway $gateway): string
    {
        return $gateway->active_mode === 'live' ? self::LIVE_URL : self::SANDBOX_URL;
    }

    private function refundUrl(PaymentGateway $gateway): string
    {
        return str_replace('/Transaction/PostTransaction', '/Refund/PostRefund', $this->baseUrl($gateway));
    }

    /** Same sorted-fields HMAC-SHA256 pattern as JazzCash/Easypaisa, using the Secured Key. */
    private function computeHash(array $fields, string $securedKey): string
    {
        ksort($fields);
        $queryString = collect($fields)->map(fn ($v, $k) => "{$k}={$v}")->implode('&');

        return hash_hmac('sha256', $queryString, $securedKey);
    }
}
