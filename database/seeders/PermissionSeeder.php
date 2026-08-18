<?php

namespace Database\Seeders;

use App\Enums\RoleNames;
use App\Models\Role;
use App\Support\Permissions\PermissionRegistry;
use App\Support\Permissions\RoleDefaultPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/**
 * Single source of truth for permission rows. Whenever a new module/CRUD is
 * added, register it in App\Support\Permissions\PermissionRegistry first,
 * then re-run this seeder:
 *
 *   php artisan db:seed --class=PermissionSeeder
 *
 * Safe to re-run: permission rows are upserted by name, and the two global
 * role templates (Super Admin, Business Admin) are fully re-synced every run
 * so they always match the registry exactly. See CLAUDE.md.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (PermissionRegistry::flat() as $name => $meta) {
                Permission::updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['is_system' => $meta['is_system']]
                );
            }

            $superAdmin = Role::firstOrCreate(
                ['name' => RoleNames::SUPERADMIN, 'business_id' => null, 'guard_name' => 'web'],
                ['description' => 'Full access to all businesses.']
            );
            $superAdmin->syncPermissions(RoleDefaultPermissions::defaultsForRole(RoleNames::SUPERADMIN));

            $businessAdmin = Role::firstOrCreate(
                ['name' => RoleNames::BUSINESSADMIN, 'business_id' => null, 'guard_name' => 'web'],
                ['description' => 'Full access to all business operations and all branch data including employees, inventory, finance, sales, purchases, reports, and settings.']
            );
            $businessAdmin->syncPermissions(RoleDefaultPermissions::defaultsForRole(RoleNames::BUSINESSADMIN));
        });
    }
}
