<?php

namespace App\Services\PaymentGateways\Providers;

use App\Contracts\PaymentGatewayProviderContract;
use App\Exceptions\PaymentGateways\PaymentGatewayException;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Services\PaymentGateways\Support\PaymentGatewayResult;
use Illuminate\Http\Request;

/**
 * Base for a provider that is registered/visible in the CMS (correct config
 * fields, correct capability flags) but has no real API integration coded
 * yet - every operation throws a clear, actionable exception instead of
 * silently doing nothing. Extend this and override supportsRefund()/
 * supportsWebhook() to match the registry entry once a real adapter is
 * written for that provider.
 */
abstract class StubProvider implements PaymentGatewayProviderContract
{
    abstract protected function providerLabel(): string;

    public function initiate(PaymentGateway $gateway, Order $order, PaymentTransaction $transaction): array
    {
        throw $this->notImplemented();
    }

    public function verify(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult
    {
        throw $this->notImplemented();
    }

    public function handleWebhook(PaymentGateway $gateway, Request $request): PaymentGatewayResult
    {
        throw $this->notImplemented();
    }

    public function refund(PaymentGateway $gateway, PaymentTransaction $transaction, float $amount): PaymentGatewayResult
    {
        throw $this->notImplemented();
    }

    public function cancel(PaymentGateway $gateway, PaymentTransaction $transaction): PaymentGatewayResult
    {
        throw $this->notImplemented();
    }

    public function supportsRefund(): bool
    {
        return false;
    }

    public function supportsWebhook(): bool
    {
        return true;
    }

    private function notImplemented(): PaymentGatewayException
    {
        return new PaymentGatewayException(
            $this->providerLabel() . ' is registered but not yet implemented. Implement ' . static::class . ' to enable it.'
        );
    }
}
