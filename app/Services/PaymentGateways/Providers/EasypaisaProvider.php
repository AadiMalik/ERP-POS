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

/**
 * Telenor Easypaisa "EasyPay" hosted-checkout integration, coded against the
 * widely-published EasyPay merchant integration guide (storeId/hashKey,
 * HMAC-SHA256-signed auto-submit form - structurally the same pattern
 * JazzCash uses). Unverified against a live Easypaisa sandbox - no test
 * credentials were available at implementation time; confirm exact field
 * names against your own Easypaisa merchant integration pack before going
 * live.
 *
 * Easypaisa has no broadly-documented public status-inquiry API, so
 * verify() does not fabricate one - it reports the transaction's current
 * stored state. The postback/webhook is this provider's authoritative
 * channel (see handleWebhook()).
 */
class EasypaisaProvider implements PaymentGatewayProviderContract
{
    private const SANDBOX_URL = 'https://easypaystg.easypaisa.com.pk/easypay/Index.jsf';
    private const LIVE_URL = 'https://easypay.easypaisa.com.pk/easypay/Index.jsf';

    public function initiate(PaymentGateway $gateway, Order $order, PaymentTransaction $transaction): array
    {
        $config = $gateway->activeConfig();
        $now = now();

        $fields = [
            'storeId' => $config['store_id'] ?? '',
            'amount' => number_format($transaction->amount, 2, '.', ''),
            'postBackURL' => $config['return_url'] ?? '',
            'orderRefNum' => $transaction->internal_reference,
            'expiryDate' => $now->copy()->addHour()->format('YmdHis'),
            'autoRedirect' => '1',
            // Mobile Account (wallet) - Easypaisa also supports an
            // Over-The-Counter method code, not exposed here.
            'paymentMethod' => 'MA',
        ];

        $fields['merchantHashedReq'] = $this->computeHash($fields, $config['hash_key'] ?? '');

        return [
            'method' => 'POST',
            'redirect_url' => $gateway->active_mode === 'live' ? self::LIVE_URL : self::SANDBOX_URL,
            'fields' => $fields,
            'gateway_reference' => $transaction->internal_reference,
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

        $receivedHash = $payload['merchantHashedResp'] ?? null;
        unset($payload['merchantHashedResp']);

        $expectedHash = $this->computeHash($payload, $config['hash_key'] ?? '');

        if (!$receivedHash || !hash_equals($expectedHash, (string) $receivedHash)) {
            throw new InvalidWebhookSignatureException('Easypaisa merchantHashedResp verification failed.');
        }

        // '0000' is EasyPay's documented success code.
        $status = ($payload['status'] ?? null) === '0000' ? 'paid' : 'failed';

        return new PaymentGatewayResult(
            status: $status,
            gatewayTransactionId: $payload['transactionId'] ?? null,
            internalReference: $payload['orderRefNum'] ?? null,
            amount: isset($payload['amount']) ? (float) $payload['amount'] : null,
            failureCode: $status === 'failed' ? ($payload['status'] ?? null) : null,
            failureReason: $status === 'failed' ? ($payload['desc'] ?? 'Unknown Easypaisa response') : null,
            eventId: ($payload['orderRefNum'] ?? '') . ':' . ($payload['transactionId'] ?? ''),
            meta: $payload,
        );
    }

    public function refund(PaymentGateway $gateway, PaymentTransaction $transaction, float $amount): PaymentGatewayResult
    {
        throw new PaymentGatewayException('Refunds are not supported for Easypaisa Mobile Account transactions.');
    }

    public function cancel(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult
    {
        return new PaymentGatewayResult(status: 'cancelled', internalReference: $transaction->internal_reference);
    }

    public function supportsRefund(): bool
    {
        return false;
    }

    public function supportsWebhook(): bool
    {
        return true;
    }

    /**
     * EasyPay hash algorithm: sort all fields alphabetically by key, join as
     * "key=value&key=value...", HMAC-SHA256 with the Hash Key, base64-encoded.
     */
    private function computeHash(array $fields, string $hashKey): string
    {
        ksort($fields);
        $queryString = collect($fields)->map(fn ($v, $k) => "{$k}={$v}")->implode('&');

        return base64_encode(hash_hmac('sha256', $queryString, $hashKey, true));
    }
}
