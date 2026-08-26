<?php

namespace Tests\Unit;

use App\Models\AccountingSetting;
use App\Models\CustomerProfile;
use App\Services\Concrete\Admin\CustomerService;
use Exception;
use Tests\TestCase;

/**
 * Exercises CustomerService receivable COA resolution without hitting the DB
 * by overriding account validation to a fixed allow-list.
 */
class TestableCustomerService extends CustomerService
{
    /** @var array<int, string> */
    public array $valid_account_ids = [];

    public function isValidReceivableAccount(?string $account_id, string $business_id): bool
    {
        return $account_id !== null && in_array($account_id, $this->valid_account_ids, true);
    }
}

class CustomerReceivableAccountResolutionTest extends TestCase
{
    protected function profile(string $account_id = null): CustomerProfile
    {
        $profile = new CustomerProfile();
        $profile->business_id = 'business-1';
        $profile->account_id = $account_id;

        return $profile;
    }

    protected function settings(string $default_account_id = null): AccountingSetting
    {
        $setting = new AccountingSetting();
        $setting->business_id = 'business-1';
        $setting->default_customer_account_id = $default_account_id;

        return $setting;
    }

    public function test_uses_customer_profile_coa_when_valid(): void
    {
        $service = new TestableCustomerService();
        $service->valid_account_ids = ['profile-coa', 'default-coa'];

        $account_id = $service->tryResolveCustomerReceivableAccountId(
            $this->profile('profile-coa'),
            $this->settings('default-coa')
        );

        $this->assertSame('profile-coa', $account_id);
    }

    public function test_falls_back_to_accounting_settings_default_when_profile_coa_missing(): void
    {
        $service = new TestableCustomerService();
        $service->valid_account_ids = ['default-coa'];

        $account_id = $service->tryResolveCustomerReceivableAccountId(
            $this->profile(null),
            $this->settings('default-coa')
        );

        $this->assertSame('default-coa', $account_id);
    }

    public function test_falls_back_to_default_when_profile_coa_is_invalid(): void
    {
        $service = new TestableCustomerService();
        $service->valid_account_ids = ['default-coa'];

        $account_id = $service->tryResolveCustomerReceivableAccountId(
            $this->profile('stale-or-invalid-coa'),
            $this->settings('default-coa')
        );

        $this->assertSame('default-coa', $account_id);
    }

    public function test_rejects_when_neither_profile_nor_default_has_valid_coa(): void
    {
        $service = new TestableCustomerService();
        $service->valid_account_ids = [];

        $this->assertNull($service->tryResolveCustomerReceivableAccountId(
            $this->profile(null),
            $this->settings(null)
        ));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(CustomerService::RECEIVABLE_COA_MISSING_MESSAGE);

        $service->resolveCustomerReceivableAccountId(
            $this->profile(null),
            $this->settings(null)
        );
    }
}
