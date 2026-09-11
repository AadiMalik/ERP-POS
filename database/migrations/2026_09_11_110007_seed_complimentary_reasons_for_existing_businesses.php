<?php

use App\Models\Business;
use App\Services\Concrete\Admin\ComplimentaryReasonService;
use Illuminate\Database\Migrations\Migration;

/**
 * Seeds the default complimentary reasons for every existing business.
 * ComplimentaryReasonService::seedDefaults() is idempotent (skips a business
 * that already has any reason rows).
 */
return new class extends Migration
{
    public function up()
    {
        $service = app(ComplimentaryReasonService::class);

        Business::query()->pluck('business_id')->each(function (string $businessId) use ($service) {
            $service->seedDefaults($businessId);
        });
    }

    public function down()
    {
        // Intentionally no-op: reasons may already be in use on orders.
    }
};
