<?php

namespace Tests\Feature\PaymentGateway;

use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PaymentGatewayWebhookLog;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Concrete\Admin\PaymentGatewayService;
use App\Services\Concrete\Admin\PaymentTransactionService;
use App\Services\Concrete\Api\PaymentService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Full payment lifecycle against App\Services\PaymentGateways\Providers\
 * DummyTestProvider - success, duplicate/replayed webhooks, invalid
 * signatures, forged/mismatched amounts, already-paid orders, and refunds.
 * No real provider (JazzCash/Stripe/...) has live sandbox credentials to
 * test against, so the dummy provider stands in behind the exact same
 * contract every real adapter implements (see PaymentGatewayProviderRegistry
 * and PaymentGatewayManager) - this proves the framework's lifecycle/
 * idempotency/security logic, not any one provider's API integration.
 */
class PaymentLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    private string $businessId;
    private User $user;
    private PaymentGateway $gateway;
    private const WEBHOOK_SECRET = 'dummy-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessId = generateUuid();
        $this->user = User::create([
            'name' => 'Payment Test User',
            'email' => 'pgw-lifecycle-' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'business_id' => $this->businessId,
            'status' => 'active',
            'is_deleted' => 0,
            'date_created' => now(),
        ]);
        $this->actingAs($this->user);

        $this->gateway = app(PaymentGatewayService::class)->save([
            'business_id' => $this->businessId,
            'provider_code' => 'dummy_test',
            'display_name' => 'Dummy Gateway',
            'active_mode' => 'sandbox',
            'website_enabled' => true,
            'mobile_enabled' => true,
            'config_sandbox' => ['webhook_secret' => self::WEBHOOK_SECRET],
        ]);
        app(PaymentGatewayService::class)->status($this->gateway->payment_gateway_id);
        $this->gateway->refresh();
    }

    private function makeOrder(float $total = 500.00): Order
    {
        return Order::create([
            'order_id' => generateUuid(),
            'daily_order_id' => random_int(1, 999999),
            'business_id' => $this->businessId,
            'branch_id' => generateUuid(),
            'warehouse_id' => generateUuid(),
            'user_id' => $this->user->id,
            'order_date' => now(),
            'sale_date' => now()->toDateString(),
            'total' => $total,
            'paid_amount' => 0,
            'status' => 'hold',
            'is_deleted' => 0,
            'date_created' => now(),
        ]);
    }

    private function webhookRequest(array $body): Request
    {
        $payload = json_encode($body);
        $signature = hash_hmac('sha256', $payload, self::WEBHOOK_SECRET);

        return Request::create(
            '/api/webhooks/payment-gateways/' . $this->businessId . '/' . $this->gateway->provider_code,
            'POST',
            [],
            [],
            [],
            ['HTTP_X_DUMMY_SIGNATURE' => $signature],
            $payload
        );
    }

    public function test_initiate_then_webhook_marks_order_paid(): void
    {
        $order = $this->makeOrder(500.00);

        $result = app(PaymentService::class)->initiate($this->businessId, $order->order_id, $this->gateway->payment_gateway_id, 'website', $this->user->id);
        $this->assertSame('pending', $result['status']);

        $transaction = PaymentTransaction::find($result['transaction_id']);
        $this->assertSame('pending', $transaction->status);

        app(PaymentService::class)->handleWebhook($this->businessId, $this->gateway->provider_code, $this->webhookRequest([
            'event_id' => 'evt-1',
            'internal_reference' => $transaction->internal_reference,
            'status' => 'paid',
            'gateway_transaction_id' => 'GW-TXN-1',
            'amount' => 500.00,
            'currency' => $transaction->currency,
        ]));

        $this->assertSame('paid', $transaction->fresh()->status);
        $this->assertEquals(500.00, $order->fresh()->paid_amount);
    }

    public function test_duplicate_webhook_event_is_a_safe_no_op(): void
    {
        $order = $this->makeOrder(300.00);
        $result = app(PaymentService::class)->initiate($this->businessId, $order->order_id, $this->gateway->payment_gateway_id, 'website', $this->user->id);
        $transaction = PaymentTransaction::find($result['transaction_id']);

        $body = [
            'event_id' => 'evt-dup',
            'internal_reference' => $transaction->internal_reference,
            'status' => 'paid',
            'amount' => 300.00,
            'currency' => $transaction->currency,
        ];

        app(PaymentService::class)->handleWebhook($this->businessId, $this->gateway->provider_code, $this->webhookRequest($body));
        // Same event id again - must not double-process or error.
        app(PaymentService::class)->handleWebhook($this->businessId, $this->gateway->provider_code, $this->webhookRequest($body));

        $this->assertSame('paid', $transaction->fresh()->status);
        $this->assertEquals(300.00, $order->fresh()->paid_amount);
        $this->assertSame(1, PaymentGatewayWebhookLog::where('event_id', 'evt-dup')->count());
    }

    public function test_invalid_signature_is_rejected_and_never_applied(): void
    {
        $order = $this->makeOrder(200.00);
        $result = app(PaymentService::class)->initiate($this->businessId, $order->order_id, $this->gateway->payment_gateway_id, 'website', $this->user->id);
        $transaction = PaymentTransaction::find($result['transaction_id']);

        $payload = json_encode([
            'event_id' => 'evt-forged',
            'internal_reference' => $transaction->internal_reference,
            'status' => 'paid',
            'amount' => 200.00,
        ]);
        $request = Request::create(
            '/api/webhooks/payment-gateways/' . $this->businessId . '/' . $this->gateway->provider_code,
            'POST', [], [], [], ['HTTP_X_DUMMY_SIGNATURE' => 'not-the-real-signature'], $payload
        );

        app(PaymentService::class)->handleWebhook($this->businessId, $this->gateway->provider_code, $request);

        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertEquals(0, $order->fresh()->paid_amount);
        $this->assertSame('invalid', PaymentGatewayWebhookLog::where('provider_code', 'dummy_test')->latest('id')->value('status'));
    }

    public function test_amount_mismatch_is_disputed_not_paid(): void
    {
        $order = $this->makeOrder(1000.00);
        $result = app(PaymentService::class)->initiate($this->businessId, $order->order_id, $this->gateway->payment_gateway_id, 'website', $this->user->id);
        $transaction = PaymentTransaction::find($result['transaction_id']);

        // Gateway claims a different (tampered/incorrect) amount than the order total.
        app(PaymentService::class)->handleWebhook($this->businessId, $this->gateway->provider_code, $this->webhookRequest([
            'event_id' => 'evt-bad-amount',
            'internal_reference' => $transaction->internal_reference,
            'status' => 'paid',
            'amount' => 1.00,
            'currency' => $transaction->currency,
        ]));

        $this->assertSame('disputed', $transaction->fresh()->status);
        $this->assertEquals(0, $order->fresh()->paid_amount);
    }

    public function test_already_paid_order_cannot_be_paid_again(): void
    {
        $order = $this->makeOrder(150.00);
        $result = app(PaymentService::class)->initiate($this->businessId, $order->order_id, $this->gateway->payment_gateway_id, 'website', $this->user->id);
        $transaction = PaymentTransaction::find($result['transaction_id']);

        app(PaymentService::class)->handleWebhook($this->businessId, $this->gateway->provider_code, $this->webhookRequest([
            'event_id' => 'evt-paid',
            'internal_reference' => $transaction->internal_reference,
            'status' => 'paid',
            'amount' => 150.00,
            'currency' => $transaction->currency,
        ]));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('already paid');
        app(PaymentService::class)->initiate($this->businessId, $order->order_id, $this->gateway->payment_gateway_id, 'website', $this->user->id);
    }

    public function test_full_and_partial_refund(): void
    {
        $order = $this->makeOrder(400.00);
        $result = app(PaymentService::class)->initiate($this->businessId, $order->order_id, $this->gateway->payment_gateway_id, 'website', $this->user->id);
        $transaction = PaymentTransaction::find($result['transaction_id']);

        app(PaymentService::class)->handleWebhook($this->businessId, $this->gateway->provider_code, $this->webhookRequest([
            'event_id' => 'evt-refund-setup',
            'internal_reference' => $transaction->internal_reference,
            'status' => 'paid',
            'amount' => 400.00,
            'currency' => $transaction->currency,
        ]));

        // Each refund's own transaction row is 'refunded' (that refund call
        // succeeded); it's the ORIGINAL transaction whose status reflects
        // full vs partial, based on cumulative refunded_amount.
        $refund = app(PaymentTransactionService::class)->refundTransaction($transaction->payment_transaction_id, 150.00);

        $this->assertSame('refunded', $refund->status);
        $this->assertSame('partially_refunded', $transaction->fresh()->status);
        $this->assertEquals(150.00, $transaction->fresh()->refunded_amount);
        $this->assertEquals(250.00, $order->fresh()->paid_amount);

        $final = app(PaymentTransactionService::class)->refundTransaction($transaction->payment_transaction_id);
        $this->assertSame('refunded', $final->status);
        $this->assertSame('refunded', $transaction->fresh()->status);
        $this->assertEquals(0, $order->fresh()->paid_amount);
    }
}
