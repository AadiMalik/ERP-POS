<?php

use App\Enums\RoleNames;
use App\Models\Role;
use App\Support\Permissions\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;

/**
 * Backward-compat data migration for Phase 2 batch A1: Inventory Manager's
 * default permission template now includes the 'purchase-return' module
 * (see RoleDefaultPermissions::defaultsForRole()). Existing businesses'
 * already-created Inventory Manager role rows don't pick up template
 * changes automatically (only RoleService::resetBusinessRoles() re-syncs
 * them), so this grants the module to every existing one directly - the
 * same backfill pattern as
 * 2026_08_23_120000_backfill_order_complete_and_pos_register_permissions.php.
 */
return new class extends Migration
{
    public function up()
    {
        $permissions = PermissionRegistry::namesForModules(['purchase-return']);

        Role::where('name', RoleNames::INVENTORYMANAGER)
            ->whereNotNull('business_id')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissions));
    }

    public function down()
    {
        $permissions = PermissionRegistry::namesForModules(['purchase-return']);

        Role::where('name', RoleNames::INVENTORYMANAGER)
            ->whereNotNull('business_id')
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permissions));
    }
};
