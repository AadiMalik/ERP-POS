<?php

namespace Database\Seeders;

use App\Models\SystemFeatureFlag;
use Illuminate\Database\Seeder;

/**
 * Registers the known System Feature Controls keys (idempotent - upserts by
 * `key`, never touches `is_enabled` on an existing row so a Super Admin's
 * choice always survives a re-seed). See SystemFeatureFlagService::isEnabled().
 */
class SystemFeatureFlagSeeder extends Seeder
{
    public function run()
    {
        $flags = [
            [
                'key' => 'push_notifications',
                'label' => 'Push Notifications (Firebase)',
                'description' => 'Platform-wide switch for FCM push notifications (broadcast campaigns and future transactional pushes). Turning this off stops all outgoing pushes for every business.',
                'category' => 'integrations',
            ],
            [
                'key' => 'online_payment_gateways',
                'label' => 'Online Payment Gateways',
                'description' => 'Platform-wide switch for online payment gateways on the Website/Mobile App checkout. Turning this off hides all gateways for every business (Cash on Delivery is unaffected).',
                'category' => 'payments',
            ],
        ];

        foreach ($flags as $flag) {
            SystemFeatureFlag::firstOrCreate(
                ['key' => $flag['key']],
                array_merge($flag, [
                    'system_feature_flag_id' => generateUuid(),
                    'is_enabled' => true,
                    'date_created' => now(),
                ])
            );
        }
    }
}
