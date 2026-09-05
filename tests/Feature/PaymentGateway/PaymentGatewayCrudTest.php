<?php

namespace Tests\Feature\PaymentGateway;

use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Concrete\Admin\PaymentGatewayService;
use App\Services\Concrete\Admin\PaymentMethodService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * CRUD + secret-masking + linked payment_methods sync for the Payment
 * Gateway CMS. Uses DatabaseTransactions (shared MySQL DB, rolled back after
 * each test) - same convention as BroadcastNotificationSendTest.
 */
class PaymentGatewayCrudTest extends TestCase
{
    use DatabaseTransactions;

    private string $businessId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessId = generateUuid();

        $user = User::create([
            'name' => 'Gateway Test User',
            'email' => 'pgw-crud-' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'business_id' => $this->businessId,
            'status' => 'active',
            'is_deleted' => 0,
            'date_created' => now(),
        ]);

        $this->actingAs($user);
    }

    public function test_creating_a_gateway_encrypts_secrets_and_creates_linked_payment_method(): void
    {
        $gateway = app(PaymentGatewayService::class)->save([
            'business_id' => $this->businessId,
            'provider_code' => 'stripe',
            'display_name' => 'Stripe',
            'active_mode' => 'sandbox',
            'website_enabled' => true,
            'mobile_enabled' => true,
            'config_sandbox' => [
                'publishable_key' => 'pk_test_123',
                'secret_key' => 'sk_test_secret',
                'webhook_secret' => 'whsec_test',
            ],
        ]);

        // Never stored in plaintext - the raw DB attribute must not equal the secret.
        $raw = $gateway->getAttributes()['config_sandbox'];
        $this->assertStringNotContainsString('sk_test_secret', (string) $raw);

        // But it decrypts correctly through the model.
        $this->assertSame('sk_test_secret', $gateway->fresh()->activeConfig()['secret_key']);

        // maskedConfig() only ever exposes booleans, never values.
        $masked = $gateway->maskedConfig('sandbox');
        $this->assertTrue($masked['secret_key']);
        $this->assertArrayNotHasKey('sk_test_secret', $masked);

        // A linked, website-only payment_methods row was auto-created.
        $this->assertNotNull($gateway->payment_method_id);
        $method = PaymentMethod::find($gateway->payment_method_id);
        $this->assertSame('gateway', $method->type);
        $this->assertEquals(1, $method->is_website_only);
    }

    public function test_saving_without_a_new_secret_keeps_the_existing_one(): void
    {
        $gateway = app(PaymentGatewayService::class)->save([
            'business_id' => $this->businessId,
            'provider_code' => 'stripe',
            'display_name' => 'Stripe',
            'active_mode' => 'sandbox',
            'config_sandbox' => ['secret_key' => 'sk_test_original'],
        ]);

        app(PaymentGatewayService::class)->save([
            'payment_gateway_id' => $gateway->payment_gateway_id,
            'business_id' => $this->businessId,
            'display_name' => 'Stripe Updated',
            'active_mode' => 'sandbox',
            // secret_key left blank on the edit form - must not wipe it.
            'config_sandbox' => ['secret_key' => ''],
        ]);

        $this->assertSame('sk_test_original', $gateway->fresh()->activeConfig()['secret_key']);
        $this->assertSame('Stripe Updated', $gateway->fresh()->display_name);
    }

    public function test_status_toggle_syncs_the_linked_payment_method_and_pos_never_sees_it(): void
    {
        $service = app(PaymentGatewayService::class);
        $gateway = $service->save([
            'business_id' => $this->businessId,
            'provider_code' => 'jazzcash',
            'display_name' => 'JazzCash',
            'active_mode' => 'sandbox',
            'config_sandbox' => ['merchant_id' => 'MC123', 'password' => 'pass', 'integrity_salt' => 'salt', 'return_url' => 'https://example.test/return'],
        ]);

        $this->assertEquals(0, PaymentMethod::find($gateway->payment_method_id)->status === 'active' ? 1 : 0);

        $service->status($gateway->payment_gateway_id);
        $gateway->refresh();
        $this->assertTrue((bool) $gateway->is_active);
        $this->assertSame('active', PaymentMethod::find($gateway->payment_method_id)->status);

        // POS's own tender list (PaymentMethodService::getAllActive()) must
        // never surface a gateway-backed method - regression guard.
        $posMethods = app(PaymentMethodService::class)->getAllActive($this->businessId);
        $this->assertFalse($posMethods->contains('payment_method_id', $gateway->payment_method_id));
    }

    public function test_deleting_a_gateway_soft_deletes_and_deactivates_but_never_hard_deletes_the_payment_method(): void
    {
        $service = app(PaymentGatewayService::class);
        $gateway = $service->save([
            'business_id' => $this->businessId,
            'provider_code' => 'stripe',
            'display_name' => 'Stripe',
            'active_mode' => 'sandbox',
            'config_sandbox' => ['secret_key' => 'sk_test'],
        ]);
        $service->status($gateway->payment_gateway_id);

        $service->delete($gateway->payment_gateway_id);

        $this->assertEquals(1, PaymentGateway::withoutGlobalScopes()->where('payment_gateway_id', $gateway->payment_gateway_id)->value('is_deleted'));
        $this->assertNotNull(PaymentMethod::find($gateway->payment_method_id));
        $this->assertSame('inactive', PaymentMethod::find($gateway->payment_method_id)->status);
    }

    public function test_cannot_add_a_second_active_gateway_for_the_same_provider(): void
    {
        $service = app(PaymentGatewayService::class);
        $first = $service->save([
            'business_id' => $this->businessId,
            'provider_code' => 'stripe',
            'display_name' => 'Stripe Main',
            'active_mode' => 'sandbox',
            'config_sandbox' => ['secret_key' => 'sk_test_1'],
        ]);
        $service->status($first->payment_gateway_id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already active for this business');
        $service->save([
            'business_id' => $this->businessId,
            'provider_code' => 'stripe',
            'display_name' => 'Stripe Duplicate',
            'active_mode' => 'sandbox',
            'config_sandbox' => ['secret_key' => 'sk_test_2'],
        ]);
    }

    public function test_deactivating_lets_a_new_gateway_be_added_and_blocks_reactivating_the_old_one(): void
    {
        $service = app(PaymentGatewayService::class);
        $first = $service->save([
            'business_id' => $this->businessId,
            'provider_code' => 'stripe',
            'display_name' => 'Stripe Old',
            'active_mode' => 'sandbox',
            'config_sandbox' => ['secret_key' => 'sk_test_old'],
        ]);
        $service->status($first->payment_gateway_id); // activate
        $service->status($first->payment_gateway_id); // deactivate

        // Now a second Stripe gateway can be added and activated.
        $second = $service->save([
            'business_id' => $this->businessId,
            'provider_code' => 'stripe',
            'display_name' => 'Stripe New',
            'active_mode' => 'sandbox',
            'config_sandbox' => ['secret_key' => 'sk_test_new'],
        ]);
        $service->status($second->payment_gateway_id);
        $this->assertTrue((bool) $second->fresh()->is_active);

        // The old one cannot be reactivated while the new one is active.
        try {
            $service->status($first->payment_gateway_id);
            $this->fail('Expected an exception when reactivating a gateway while another is active.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('already active', $e->getMessage());
        }
        $this->assertFalse((bool) $first->fresh()->is_active);

        // Deactivate the new one first, then the old one can be reactivated.
        $service->status($second->payment_gateway_id);
        $service->status($first->payment_gateway_id);
        $this->assertTrue((bool) $first->fresh()->is_active);
    }
}
