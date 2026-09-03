<?php

use App\Models\Business;
use App\Services\Concrete\Admin\AccountingSettingCloneService;
use App\Services\Concrete\Admin\ChartOfAccountsCloneService;
use Database\Seeders\ChartOfAccountsTemplateSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * ChartOfAccountsTemplateSeeder now also seeds a "Loyalty Points Discount"
 * template account (code 530003-001) and the template
 * AccountingSetting.default_loyalty_discount_account_id mapping. Businesses
 * registered before this feature shipped never received it, since it's only
 * ever provisioned at business-creation time
 * (AccountingSetupWizardService::setupForBusiness()). Re-run the (idempotent)
 * template seeder, then re-run the same two clone services for every
 * existing business so each gets its own cloned Loyalty Points Discount
 * account and has default_loyalty_discount_account_id mapped to it - without
 * duplicating or touching any account/setting the business already has.
 */
return new class extends Migration
{
    public function up()
    {
        (new ChartOfAccountsTemplateSeeder())->run();

        $coaCloneService = app(ChartOfAccountsCloneService::class);
        $settingCloneService = app(AccountingSettingCloneService::class);

        Business::query()->pluck('business_id')->each(function (string $businessId) use ($coaCloneService, $settingCloneService) {
            $accountIdMap = $coaCloneService->cloneTemplateToBusiness($businessId);
            $settingCloneService->cloneTemplateToBusiness($businessId, $accountIdMap);
        });
    }

    public function down()
    {
        // Intentionally no-op: this migration only backfills missing accounts/
        // mappings for existing businesses - it never removes data another
        // business may already be posting loyalty discounts against.
    }
};
