<?php

namespace Tests\Feature\PaymentGateway;

use App\Models\User;
use App\Services\Concrete\Admin\PaymentGatewayService;
use App\Services\Concrete\Api\PaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * A gateway activated/configured in the CMS becomes available to Website/
 * Mobile only according to its own platform/currency/active settings -
 * never automatically to both, and never if inactive or unconfigured.
 */
class GatewayAvailabilityApiTest extends TestCase
{
    use DatabaseTransactions;

    private string $businessId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessId = generateUuid();
        $user = User::create([
            'name' => 'Gateway Availability Test User',
            'email' => 'pgw-avail-' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'business_id' => $this->businessId,
            'status' => 'active',
            'is_deleted' => 0,
            'date_created' => now(),
        ]);
        $this->actingAs($user);
    }

    public function test_platform_and_active_flags_filter_correctly(): void
    {
        $service = app(PaymentGatewayService::class);

        $websiteOnly = $service->save([
            'business_id' => $this->businessId,
            'provider_code' => 'dummy_test',
            'display_name' => 'Website Only Gateway',
            'active_mode' => 'sandbox',
            'website_enabled' => true,
            'mobile_enabled' => false,
            'config_sandbox' => ['webhook_secret' => 'x'],
        ]);
        $service->status($websiteOnly->payment_gateway_id);

        $mobileOnly = $service->save([
            'business_id' => $this->businessId,
            'provider_code' => 'stripe',
            'display_name' => 'Mobile Only Gateway',
            'active_mode' => 'sandbox',
            'website_enabled' => false,
            'mobile_enabled' => true,
            'config_sandbox' => ['publishable_key' => 'pk', 'secret_key' => 'sk', 'webhook_secret' => 'wh'],
        ]);
        $service->status($mobileOnly->payment_gateway_id);

        // Active on both, but never configured (no credentials) - must never appear.
        $unconfigured = $service->save([
            'business_id' => $this->businessId,
            'provider_code' => 'jazzcash',
            'display_name' => 'Unconfigured Gateway',
            'active_mode' => 'sandbox',
        ]);
        $service->status($unconfigured->payment_gateway_id);

        // Correctly configured but left inactive - must never appear.
        $inactive = $service->save([
            'business_id' => $this->businessId,
            'provider_code' => 'safepay',
            'display_name' => 'Inactive Gateway',
            'active_mode' => 'sandbox',
            'config_sandbox' => ['api_key' => 'k', 'api_secret' => 's', 'webhook_secret' => 'w'],
        ]);

        $payment_service = app(PaymentService::class);

        $website = collect($payment_service->listAvailableGateways($this->businessId, 'website'));
        $mobile = collect($payment_service->listAvailableGateways($this->businessId, 'mobile'));

        $this->assertTrue($website->contains('payment_gateway_id', $websiteOnly->payment_gateway_id));
        $this->assertFalse($website->contains('payment_gateway_id', $mobileOnly->payment_gateway_id));
        $this->assertFalse($website->contains('payment_gateway_id', $unconfigured->payment_gateway_id));
        $this->assertFalse($website->contains('payment_gateway_id', $inactive->payment_gateway_id));

        $this->assertTrue($mobile->contains('payment_gateway_id', $mobileOnly->payment_gateway_id));
        $this->assertFalse($mobile->contains('payment_gateway_id', $websiteOnly->payment_gateway_id));
    }

    public function test_currency_filter_excludes_unsupported_currencies(): void
    {
        $service = app(PaymentGatewayService::class);
        $gateway = $service->save([
            'business_id' => $this->businessId,
            'provider_code' => 'dummy_test',
            'display_name' => 'PKR Only Gateway',
            'active_mode' => 'sandbox',
            'website_enabled' => true,
            'supported_currencies' => ['PKR'],
            'config_sandbox' => ['webhook_secret' => 'x'],
        ]);
        $service->status($gateway->payment_gateway_id);

        $payment_service = app(PaymentService::class);
        $forPkr = collect($payment_service->listAvailableGateways($this->businessId, 'website', 'PKR'));
        $forUsd = collect($payment_service->listAvailableGateways($this->businessId, 'website', 'USD'));

        $this->assertTrue($forPkr->contains('payment_gateway_id', $gateway->payment_gateway_id));
        $this->assertFalse($forUsd->contains('payment_gateway_id', $gateway->payment_gateway_id));
    }

    /** Never crashes/exposes secrets even for a stub provider with no real adapter yet. */
    public function test_public_gateway_listing_never_exposes_credentials(): void
    {
        $service = app(PaymentGatewayService::class);
        $gateway = $service->save([
            'business_id' => $this->businessId,
            'provider_code' => 'stripe',
            'display_name' => 'Stripe',
            'active_mode' => 'sandbox',
            'website_enabled' => true,
            'config_sandbox' => ['publishable_key' => 'pk_test', 'secret_key' => 'sk_test_super_secret', 'webhook_secret' => 'whsec'],
        ]);
        $service->status($gateway->payment_gateway_id);

        $listing = app(PaymentService::class)->listAvailableGateways($this->businessId, 'website');
        $this->assertNotEmpty($listing, 'Expected the gateway to actually appear in the listing for this assertion to be meaningful.');
        $encoded = json_encode($listing);

        $this->assertStringNotContainsString('sk_test_super_secret', $encoded);
        $this->assertStringNotContainsString('whsec', $encoded);
    }
}
