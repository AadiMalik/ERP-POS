<?php

namespace App\Services\Concrete\Api;

use App\Exceptions\PaymentGateways\InvalidWebhookSignatureException;
use App\Models\AccountingSetting;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PaymentGatewayWebhookLog;
use App\Models\PaymentTransaction;
use App\Services\Concrete\Admin\SystemFeatureFlagService;
use App\Services\PaymentGateways\PaymentGatewayManager;
use App\Services\PaymentGateways\PaymentGatewayProviderRegistry;
use App\Services\PaymentGateways\Support\PaymentGatewayResult;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Website/Mobile gateway payment lifecycle: list available gateways, start a
 * payment against an already-created hold order, verify it directly with the
 * provider, and apply provider webhooks - the single place any Payment
 * Gateway transaction state change happens. Never trusts a frontend "success"
 * redirect alone; every state change re-validates against the authoritative
 * Order/PaymentTransaction rows. Used identically by website and mobile app
 * (a `platform` flag is the only difference) - no per-platform subclass,
 * mirroring how MobileCheckoutService needed none either.
 */
class PaymentService
{
    protected $payment_gateway_manager;
    protected $checkout_service;

    public function __construct(PaymentGatewayManager $payment_gateway_manager, WebsiteCheckoutService $checkout_service)
    {
        $this->payment_gateway_manager = $payment_gateway_manager;
        $this->checkout_service = $checkout_service;
    }

    public function listAvailableGateways(string $business_id, string $platform, ?string $currency = null): array
    {
        if (!app(SystemFeatureFlagService::class)->isEnabled('online_payment_gateways')) {
            return [];
        }

        $column = $platform === 'mobile' ? 'mobile_enabled' : 'website_enabled';

        $gateways = PaymentGateway::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('is_active', 1)
            ->where($column, 1)
            ->orderBy('sort_order')
            ->get();

        $out = [];
        foreach ($gateways as $gateway) {
            if (!$gateway->isReadyForCheckout()) {
                continue;
            }

            $currencies = $gateway->supported_currencies ?: [];
            if ($currency && $currencies && !in_array(strtoupper($currency), array_map('strtoupper', $currencies), true)) {
                continue;
            }

            $provider = PaymentGatewayProviderRegistry::find($gateway->provider_code);

            $out[] = [
                'payment_gateway_id' => $gateway->payment_gateway_id,
                'payment_method_id' => $gateway->payment_method_id,
                'provider_code' => $gateway->provider_code,
                'name' => $gateway->display_name,
                'description' => $gateway->description,
                'logo_url' => $gateway->logo_path ? asset('public/uploads/payment_gateway_logo/' . $gateway->logo_path) : null,
                'environment' => $gateway->active_mode,
                'supported_payment_methods' => $currencies ? $gateway->supported_payment_methods : ($provider['payment_methods'] ?? []),
                'supported_currencies' => $currencies ?: ($provider['currencies'] ?? []),
            ];
        }

        return $out;
    }

    /**
     * Starts a payment against an already-created hold order (see
     * WebsiteCheckoutService::placeOrder()). Guards: order belongs to this
     * business/customer, order isn't already paid, gateway is active for
     * this platform and fully configured.
     */
    public function initiate(string $business_id, string $order_id, string $payment_gateway_id, string $platform, int $user_id): array
    {
        $order = Order::where('order_id', $order_id)
            ->where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->where('is_deleted', 0)
            ->firstOrFail();

        if ((float) $order->paid_amount >= (float) $order->total - 0.001) {
            throw new Exception('This order is already paid.');
        }

        $active_transaction_exists = PaymentTransaction::where('order_id', $order_id)
            ->whereIn('status', ['paid', 'authorized'])
            ->exists();

        if ($active_transaction_exists) {
            throw new Exception('This order already has an active payment.');
        }

        $gateway = PaymentGateway::where('payment_gateway_id', $payment_gateway_id)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('is_active', 1)
            ->where($platform === 'mobile' ? 'mobile_enabled' : 'website_enabled', 1)
            ->firstOrFail();

        if (!$gateway->isReadyForCheckout()) {
            throw new Exception('This payment gateway is not fully configured.');
        }

        $transaction = PaymentTransaction::create([
            'payment_transaction_id' => generateUuid(),
            'business_id' => $business_id,
            'order_id' => $order_id,
            'user_id' => $user_id,
            'payment_gateway_id' => $gateway->payment_gateway_id,
            'provider_code' => $gateway->provider_code,
            'environment' => $gateway->active_mode,
            'payment_method_code' => $gateway->provider_code,
            'client_platform' => $platform,
            'internal_reference' => (string) Str::uuid(),
            'amount' => $order->total,
            'currency' => AccountingSetting::where('business_id', $business_id)->value('currency') ?? 'PKR',
            'status' => 'initiated',
            'createdby_id' => $user_id,
            'date_created' => now(),
        ]);

        $adapter = $this->payment_gateway_manager->adapterFor($gateway);
        $result = $adapter->initiate($gateway, $order, $transaction);

        $transaction->update([
            'gateway_reference' => $result['gateway_reference'] ?? null,
            'gateway_transaction_id' => $result['gateway_transaction_id'] ?? null,
            'status' => 'pending',
            'date_updated' => now(),
        ]);

        return array_merge($result, [
            'transaction_id' => $transaction->payment_transaction_id,
            'status' => 'pending',
        ]);
    }

    /** Cheap status poll - no provider call, just the current stored state. */
    public function status(string $business_id, string $payment_transaction_id, int $user_id): array
    {
        $transaction = PaymentTransaction::where('payment_transaction_id', $payment_transaction_id)
            ->where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->firstOrFail();

        return $this->publicTransaction($transaction);
    }

    /** Server-to-server re-check with the provider - never trust the frontend's redirect alone. */
    public function verify(string $business_id, string $payment_transaction_id, int $user_id): array
    {
        return DB::transaction(function () use ($business_id, $payment_transaction_id, $user_id) {
            $transaction = PaymentTransaction::where('payment_transaction_id', $payment_transaction_id)
                ->where('business_id', $business_id)
                ->where('user_id', $user_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->isTerminal()) {
                return $this->publicTransaction($transaction);
            }

            $gateway = PaymentGateway::findOrFail($transaction->payment_gateway_id);
            $adapter = $this->payment_gateway_manager->adapterFor($gateway);
            $result = $adapter->verify($gateway, $transaction);

            $this->applyResult($transaction, $result, 'api_poll');

            return $this->publicTransaction($transaction->fresh());
        });
    }

    /**
     * Applies a webhook/callback. The webhook is the authoritative payment
     * synchronization mechanism: even a delayed webhook safely updates a
     * still-pending order. Duplicate/replayed events (same provider event id)
     * are hard-blocked before anything else runs.
     *
     * Resolved by (business_id, provider_code) rather than payment_gateway_id
     * on purpose: both are known and chosen in the CMS *before* a gateway is
     * ever saved, so the webhook URL can be shown - and given to the
     * provider's dashboard - immediately, with no chicken-and-egg problem
     * where you'd need the row's id (which only exists after saving) before
     * you can create the provider's own webhook (which needs that URL).
     * Always targets the currently *active* row for that pair, matching the
     * "only one active gateway per provider per business" rule.
     */
    public function handleWebhook(string $business_id, string $provider_code, Request $request): void
    {
        $gateway = PaymentGateway::where('business_id', $business_id)
            ->where('provider_code', $provider_code)
            ->where('is_active', 1)
            ->where('is_deleted', 0)
            ->firstOrFail();

        $adapter = $this->payment_gateway_manager->adapterFor($gateway);

        try {
            $result = $adapter->handleWebhook($gateway, $request);
        } catch (InvalidWebhookSignatureException $e) {
            PaymentGatewayWebhookLog::create([
                'provider_code' => $gateway->provider_code,
                'business_id' => $gateway->business_id,
                'event_id' => 'invalid:' . sha1($request->getContent() . microtime()),
                'payload_hash' => sha1($request->getContent()),
                'status' => 'invalid',
                'received_at' => now(),
            ]);
            return;
        }

        $event_id = $result->eventId ?: sha1($request->getContent());

        try {
            PaymentGatewayWebhookLog::create([
                'provider_code' => $gateway->provider_code,
                'business_id' => $gateway->business_id,
                'event_id' => $event_id,
                'payload_hash' => sha1($request->getContent()),
                'status' => 'processed',
                'received_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Unique (provider_code, event_id) violation - this exact event was already processed. Idempotent no-op.
            return;
        }

        DB::transaction(function () use ($result, $gateway) {
            $query = PaymentTransaction::where('payment_gateway_id', $gateway->payment_gateway_id);

            if ($result->internalReference) {
                $query->where('internal_reference', $result->internalReference);
            } elseif ($result->gatewayTransactionId) {
                $query->where('gateway_transaction_id', $result->gatewayTransactionId);
            } else {
                return;
            }

            $transaction = $query->lockForUpdate()->first();

            if (!$transaction || $transaction->isTerminal()) {
                return;
            }

            $this->applyResult($transaction, $result, 'webhook');
        });
    }

    /**
     * Validates the result against the authoritative transaction (amount/
     * currency tamper guard) then updates state, and syncs the order only on
     * a genuine 'paid' outcome.
     */
    protected function applyResult(PaymentTransaction $transaction, PaymentGatewayResult $result, string $method): void
    {
        if ($transaction->isTerminal()) {
            return;
        }

        if ($result->amount !== null && abs($result->amount - (float) $transaction->amount) > 0.01) {
            $transaction->update([
                'status' => 'disputed',
                'failure_reason' => 'Amount reported by gateway does not match the order amount.',
                'verification_method' => $method,
                'verified_at' => now(),
                'date_updated' => now(),
            ]);
            return;
        }

        if ($result->currency !== null && strtoupper($result->currency) !== strtoupper($transaction->currency)) {
            $transaction->update([
                'status' => 'disputed',
                'failure_reason' => 'Currency reported by gateway does not match the order currency.',
                'verification_method' => $method,
                'verified_at' => now(),
                'date_updated' => now(),
            ]);
            return;
        }

        $transaction->update([
            'status' => $result->status,
            'gateway_transaction_id' => $result->gatewayTransactionId ?: $transaction->gateway_transaction_id,
            'failure_code' => $result->failureCode,
            'failure_reason' => $result->failureReason,
            'verification_method' => $method,
            'verified_at' => now(),
            'meta' => array_merge($transaction->meta ?? [], $result->meta),
            'date_updated' => now(),
        ]);

        if ($result->status === 'paid') {
            $this->checkout_service->applyGatewayPaymentSuccess($transaction);
        }
    }

    /** Safe fields only - never gateway config/credentials. */
    protected function publicTransaction(PaymentTransaction $transaction): array
    {
        return [
            'transaction_id' => $transaction->payment_transaction_id,
            'order_id' => $transaction->order_id,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'failure_reason' => $transaction->failure_reason,
        ];
    }
}
