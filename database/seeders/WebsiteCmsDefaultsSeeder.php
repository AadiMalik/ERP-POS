<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Services\Concrete\Admin\WebsiteCmsDefaultsService;
use Illuminate\Database\Seeder;

/**
 * Backfills Website CMS starter content (pages, FAQs, social links,
 * homepage sections) for every existing business. New businesses get this
 * automatically via BusinessService::save() - this seeder only covers
 * businesses that already existed before the Website CMS module was added.
 * Safe to re-run: WebsiteCmsDefaultsService::seed() skips any table that
 * already has rows for the business.
 */
class WebsiteCmsDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(WebsiteCmsDefaultsService::class);

        Business::where('is_deleted', 0)->get()->each(function ($business) use ($service) {
            $service->seed($business->business_id);
        });
    }
}
