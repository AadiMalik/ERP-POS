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
use Illuminate\Support\Facades\Log;

/**
 * JazzCash Mobile Wallet (MWALLET) HashRequest integration, coded against
 * JazzCash's publicly documented HashRequest API. Unverified against a live
 * JazzCash sandbox - no test credentials were available at implementation
 * time (see resources/docs/developer's payment gateway framework doc).
 *
 * Flow: initiate() builds an auto-submit-form payload (JazzCash's checkout is
 * a hosted page reached via an HTML form POST, not a simple redirect link) -
 * the frontend renders/auto-submits it in a WebView/iframe. JazzCash then
 * redirects the customer back to pp_ReturnURL and/or posts a server callback,
 * both of which land on our webhook route and are verified via
 * handleWebhook().
 */
class JazzCashProvider implements PaymentGatewayProviderContract
{
    private const SANDBOX_URL = 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/';
    private const LIVE_URL = 'https://payments.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/';
    private const SANDBOX_INQUIRY_URL = 'https://sandbox.jazzcash.com.pk/ApplicationAPI/API/PaymentInquiry/Inquire';
    private const LIVE_INQUIRY_URL = 'https://payments.jazzcash.com.pk/ApplicationAPI/API/PaymentInquiry/Inquire';

    public function initiate(PaymentGateway $gateway, Order $order, PaymentTransaction $transaction): array
    {
        $config = $gateway->activeConfig();
        $now = now();

        $fields = [
            'pp_Version' => '1.1',
            'pp_TxnType' => 'MWALLET',
            'pp_Language' => 'EN',
            'pp_MerchantID' => $config['merchant_id'] ?? '',
            'pp_Password' => $config['password'] ?? '',
            'pp_TxnRefNo' => $transaction->internal_reference,
            // JazzCash amounts are in paisas (amount * 100), integer, no decimal point.
            'pp_Amount' => (string) (int) round($transaction->amount * 100),
            'pp_TxnCurrency' => $transaction->currency,
            'pp_TxnDateTime' => $now->format('YmdHis'),
            'pp_BillReference' => 'ORD-' . $order->order_id,
            'pp_Description' => 'Order ' . ($order->daily_order_id ?? $order->order_id),
            'pp_TxnExpiryDateTime' => $now->copy()->addHour()->format('YmdHis'),
            'pp_ReturnURL' => $config['return_url'] ?? '',
        ];

        $fields['pp_SecureHash'] = $this->computeSecureHash($fields, $config['integrity_salt'] ?? '');

        return [
            'method' => 'POST',
            'redirect_url' => $gateway->active_mode === 'live' ? self::LIVE_URL : self::SANDBOX_URL,
            'fields' => $fields,
            'gateway_reference' => $transaction->internal_reference,
        ];
    }

    public function verify(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult
    {
        $config = $gateway->activeConfig();

        $fields = [
            'pp_MerchantID' => $config['merchant_id'] ?? '',
            'pp_Password' => $config['password'] ?? '',
            'pp_TxnRefNo' => $transaction->internal_reference,
        ];
        $fields['pp_SecureHash'] = $this->computeSecureHash($fields, $config['integrity_salt'] ?? '');

        $url = $gateway->active_mode === 'live' ? self::LIVE_INQUIRY_URL : self::SANDBOX_INQUIRY_URL;

        try {
            $response = Http::timeout(15)->asForm()->post($url, $fields)->json();
        } catch (\Throwable $e) {
            Log::warning('JazzCash status inquiry failed', ['transaction' => $transaction->payment_transaction_id]);

            // Inquiry unreachable - stay pending, the webhook remains authoritative.
            return new PaymentGatewayResult(status: 'pending', internalReference: $transaction->internal_reference);
        }

        return $this->mapResponse($response ?? []);
    }

    public function handleWebhook(PaymentGateway $gateway, Request $request): PaymentGatewayResult
    {
        $config = $gateway->activeConfig();
        $payload = $request->all();

        $received_hash = $payload['pp_SecureHash'] ?? null;
        unset($payload['pp_SecureHash']);

        $expected_hash = $this->computeSecureHash($payload, $config['integrity_salt'] ?? '');

        if (!$received_hash || !hash_equals($expected_hash, $received_hash)) {
            throw new InvalidWebhookSignatureException('JazzCash SecureHash verification failed.');
        }

        $result = $this->mapResponse($payload);

        return new PaymentGatewayResult(
            status: $result->status,
            gatewayTransactionId: $result->gatewayTransactionId,
            internalReference: $payload['pp_TxnRefNo'] ?? null,
            amount: isset($payload['pp_Amount']) ? ((float) $payload['pp_Amount']) / 100 : null,
            currency: $payload['pp_TxnCurrency'] ?? null,
            failureCode: $result->failureCode,
            failureReason: $result->failureReason,
            eventId: ($payload['pp_TxnRefNo'] ?? '') . ':' . ($payload['pp_RetreivalReferenceNo'] ?? $payload['pp_TxnDateTime'] ?? ''),
            meta: $payload,
        );
    }

    public function refund(PaymentGateway $gateway, PaymentTransaction $transaction, float $amount): PaymentGatewayResult
    {
        throw new PaymentGatewayException('Refunds are not supported for JazzCash Mobile Wallet transactions.');
    }

    public function cancel(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult
    {
        // JazzCash sessions self-expire (pp_TxnExpiryDateTime) - nothing to call remotely.
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

    private function mapResponse(array $payload): PaymentGatewayResult
    {
        $code = $payload['pp_ResponseCode'] ?? null;
        $status = $code === '000' ? 'paid' : ($code === null ? 'unknown' : 'failed');

        return new PaymentGatewayResult(
            status: $status,
            gatewayTransactionId: $payload['pp_RetreivalReferenceNo'] ?? null,
            internalReference: $payload['pp_TxnRefNo'] ?? null,
            failureCode: $status === 'failed' ? $code : null,
            failureReason: $status === 'failed' ? ($payload['pp_ResponseMessage'] ?? 'Unknown JazzCash response') : null,
            meta: $payload,
        );
    }

    /**
     * JazzCash HashRequest algorithm: sort all pp_ fields alphabetically by
     * key, join their values with '&', prepend the Integrity Salt, then
     * HMAC-SHA256 the whole string using the Integrity Salt as key.
     */
    private function computeSecureHash(array $fields, string $integritySalt): string
    {
        ksort($fields);
        $values = implode('&', array_map('strval', array_values($fields)));
        $hashString = $integritySalt . '&' . $values;

        return strtoupper(hash_hmac('sha256', $hashString, $integritySalt));
    }
}
