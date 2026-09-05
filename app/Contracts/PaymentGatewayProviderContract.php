<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Services\PaymentGateways\Support\PaymentGatewayResult;
use Illuminate\Http\Request;

/**
 * Every payment gateway provider (JazzCash, Stripe, ...) implements this so
 * checkout/webhook/refund code never has to branch on provider identity -
 * see App\Services\PaymentGateways\PaymentGatewayManager for how a
 * PaymentGateway row resolves to its adapter, and
 * App\Services\PaymentGateways\PaymentGatewayProviderRegistry for how a new
 * provider is registered. Adding a gateway is: implement this contract +
 * add one registry entry - never touch checkout/order logic.
 */
interface PaymentGatewayProviderContract
{
    /**
     * Start a payment for this order. Returns whatever the provider's client
     * flow needs (redirect_url + form fields, or a client_secret, etc) -
     * shape genuinely varies by provider, so this stays a plain array rather
     * than a rigid DTO.
     */
    public function initiate(PaymentGateway $gateway, Order $order, PaymentTransaction $transaction): array;

    /** Server-to-server status check directly with the provider - never trust a frontend redirect alone. */
    public function verify(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult;

    /**
     * Verify + parse an inbound webhook/callback request. MUST throw
     * InvalidWebhookSignatureException when the signature does not check out
     * - never return a result for an unverified request.
     */
    public function handleWebhook(PaymentGateway $gateway, Request $request): PaymentGatewayResult;

    public function refund(PaymentGateway $gateway, PaymentTransaction $transaction, float $amount): PaymentGatewayResult;

    public function cancel(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult;

    public function supportsRefund(): bool;

    public function supportsWebhook(): bool;
}
