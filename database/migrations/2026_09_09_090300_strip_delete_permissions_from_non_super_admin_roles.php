<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Retroactive data cleanup for the global delete-permission lockdown
     * (see PermissionRegistry::withoutDeleteActions() / RoleService::syncPermissions()).
     * That change only stops a `.delete` permission from being *written* to a
     * non-Super-Admin role going forward - it does not touch permissions
     * already sitting in role_has_permissions/model_has_permissions from
     * before the change shipped. This runs once, on every environment, via
     * the normal `php artisan migrate`.
     *
     * @return void
     */
    public function up()
    {
        $deletePermissionIds = DB::table('permissions')->where('name', 'like', '%.delete')->pluck('id');

        if ($deletePermissionIds->isEmpty()) {
            return;
        }

        // The true global Super Admin role: business_id IS NULL AND name = 'Super Admin'.
        $superAdminRoleIds = DB::table('roles')
            ->whereNull('business_id')
            ->where('name', 'Super Admin')
            ->pluck('id');

        DB::table('role_has_permissions')
            ->whereIn('permission_id', $deletePermissionIds)
            ->whereNotIn('role_id', $superAdminRoleIds)
            ->delete();

        // Defensive: strip any `.delete` permission assigned directly to a
        // user (bypassing a role) too, unless that user holds the global
        // Super Admin role.
        $superAdminUserIds = DB::table('model_has_roles')
            ->whereIn('role_id', $superAdminRoleIds)
            ->where('model_type', 'App\\Models\\User')
            ->pluck('model_id');

        DB::table('model_has_permissions')
            ->whereIn('permission_id', $deletePermissionIds)
            ->where('model_type', 'App\\Models\\User')
            ->whereNotIn('model_id', $superAdminUserIds)
            ->delete();
    }

    /**
     * Irreversible by design - which specific roles/users a stripped
     * `.delete` permission came from is not recorded anywhere to restore.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
