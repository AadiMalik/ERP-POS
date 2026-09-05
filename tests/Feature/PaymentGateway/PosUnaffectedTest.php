<?php

namespace Tests\Feature\PaymentGateway;

use App\Models\User;
use App\Services\Concrete\Admin\PaymentGatewayService;
use App\Services\Concrete\Admin\PaymentMethodService;
use App\Services\Concrete\Api\WebsiteCheckoutService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Regression guard: the Payment Gateway framework must never leak into POS,
 * and must never widen the existing website/mobile COD+Bank-only checkout
 * contract beyond adding gateway methods explicitly chosen by
 * payment_method_id - see CLAUDE.md's explicit POS-separation requirement.
 */
class PosUnaffectedTest extends TestCase
{
    use DatabaseTransactions;

    private string $businessId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessId = generateUuid();
        $user = User::create([
            'name' => 'POS Regression Test User',
            'email' => 'pgw-pos-' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'business_id' => $this->businessId,
            'status' => 'active',
            'is_deleted' => 0,
            'date_created' => now(),
        ]);
        $this->actingAs($user);
    }

    public function test_active_gateway_never_appears_in_pos_or_website_cod_bank_listing(): void
    {
        $gateway_service = app(PaymentGatewayService::class);
        $gateway = $gateway_service->save([
            'business_id' => $this->businessId,
            'provider_code' => 'dummy_test',
            'display_name' => 'Test Gateway',
            'active_mode' => 'sandbox',
            'config_sandbox' => ['webhook_secret' => 'x'],
        ]);
        $gateway_service->status($gateway->payment_gateway_id);

        // POS's own tender list must never include it.
        $posMethods = app(PaymentMethodService::class)->getAllActive($this->businessId);
        $this->assertFalse($posMethods->contains('payment_method_id', $gateway->payment_method_id));
        $this->assertTrue($posMethods->pluck('type')->every(fn ($type) => $type !== 'gateway'));

        // The existing website COD/Bank listing endpoint stays exactly what
        // it was before this framework existed - the new payment-gateways
        // endpoint is a separate, additive listing.
        $websiteMethods = collect(app(WebsiteCheckoutService::class)->getWebsitePaymentMethods($this->businessId));
        $this->assertTrue($websiteMethods->pluck('type')->every(fn ($type) => in_array($type, ['cod', 'bank'], true)));
        $this->assertFalse($websiteMethods->contains('id', $gateway->payment_method_id));
    }
}
