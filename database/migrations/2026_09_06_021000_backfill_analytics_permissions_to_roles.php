<?php

use App\Enums\RoleNames;
use App\Models\Role;
use App\Support\Permissions\PermissionRegistry;
use App\Support\Permissions\RoleDefaultPermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Backfill analytics.view / analytics.export onto every role whose current
 * RoleDefaultPermissions template includes the analytics module. Existing
 * business-scoped role rows (Branch Admin, General Manager, Reporting
 * Analyst, ...) were created before analytics existed and do not pick up
 * template changes until resetBusinessRoles() — same pattern as
 * 2026_08_23_130000_backfill_inventory_manager_purchase_return_permissions.
 *
 * Global Business Admin (business_id null) is also covered; PermissionSeeder
 * already syncs it, but givePermissionTo is idempotent and keeps environments
 * that skipped a seeder run consistent.
 */
return new class extends Migration
{
    public function up()
    {
        $permissions = PermissionRegistry::namesForModules(['analytics']);

        $roleNames = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::BRANCHADMIN,
            RoleNames::GENERALMANAGER,
            RoleNames::REPORTINGANALYST,
        ];

        Role::whereIn('name', $roleNames)
            ->get()
            ->each(function (Role $role) use ($permissions) {
                // Only grant if the live template still includes analytics for
                // this role name — protects against a future template change
                // accidentally re-granting via migrate:fresh + this migration.
                $defaults = RoleDefaultPermissions::defaultsForRole($role->name);
                $grant = array_values(array_intersect($permissions, $defaults));
                if ($grant !== []) {
                    $role->givePermissionTo($grant);
                }
            });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down()
    {
        $permissions = PermissionRegistry::namesForModules(['analytics']);

        Role::whereIn('name', [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::BRANCHADMIN,
            RoleNames::GENERALMANAGER,
            RoleNames::REPORTINGANALYST,
        ])
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permissions));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
