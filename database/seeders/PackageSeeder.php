<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Legacy USD catalog (Starter / Professional / Enterprise).
     * Public PKR Intro plans live in IntroPackageCatalogSeeder (4 tiers × monthly/yearly).
     * This seeder only soft-hides outdated Professional / Basic Plan rows so both
     * seeders can run from DatabaseSeeder without polluting pricing UIs.
     */
    public function run()
    {
        Package::where('is_deleted', 0)
            ->whereIn('name', ['Professional', 'Basic Plan'])
            ->update([
                'status' => 0,
                'date_updated' => now(),
            ]);
    }
}
