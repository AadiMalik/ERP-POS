<?php

use App\Models\Business;
use App\Services\Concrete\Admin\AccountingSettingCloneService;
use App\Services\Concrete\Admin\ChartOfAccountsCloneService;
use Database\Seeders\ChartOfAccountsTemplateSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Seeds Complimentary / Promotional Expense (540001-002) on the global COA
 * template and maps AccountingSetting.default_complimentary_expense_account_id,
 * then clones the missing account + mapping onto every existing business.
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
        // Intentionally no-op: existing businesses may already be posting
        // complimentary expense against the cloned account.
    }
};
