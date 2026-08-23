<?php

use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Backward-compat data migration for three new, previously-ungated
 * capabilities added to PermissionRegistry (order.complete, the pos-register
 * CRUD module, and pos.register.cash-movement.manage): grants them to
 * existing business-scoped roles that already had equivalent effective
 * access under the old (ungated) behavior, so no live tenant's workflow
 * breaks the moment this ships. See CLAUDE.md's Permissions & Access
 * Control section and Phase 1 plan batch A1/A3/A4.
 *
 * Runs PermissionSeeder first (the project's single source of truth for
 * permission rows - see PermissionSeeder's docblock) so the new permission
 * rows exist before roles are granted them; this does not duplicate any
 * permission definition, it only invokes the existing seeder.
 */
return new class extends Migration
{
    public function up()
    {
        (new PermissionSeeder())->run();

        // order.complete: any business-scoped role that can already create
        // orders could already complete them under the old ungated route.
        Role::whereNotNull('business_id')
            ->whereHas('permissions', fn ($q) => $q->where('name', 'order.create'))
            ->get()
            ->each(function (Role $role) {
                if (!$role->hasPermissionTo('order.complete')) {
                    $role->givePermissionTo('order.complete');
                }
            });

        // pos-register.* + cash-movement.manage: any role that already had
        // pos.register.close (today's closest thing to "register supervisor")
        // could already manage registers/other cashiers' cash movements
        // under the old ungated routes.
        Role::whereNotNull('business_id')
            ->whereHas('permissions', fn ($q) => $q->where('name', 'pos.register.close'))
            ->get()
            ->each(function (Role $role) {
                $role->givePermissionTo([
                    'pos-register.view',
                    'pos-register.create',
                    'pos-register.edit',
                    'pos-register.delete',
                    'pos.register.cash-movement.manage',
                ]);
            });
    }

    public function down()
    {
        Role::whereNotNull('business_id')
            ->whereHas('permissions', fn ($q) => $q->where('name', 'order.create'))
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo('order.complete'));

        Role::whereNotNull('business_id')
            ->whereHas('permissions', fn ($q) => $q->where('name', 'pos.register.close'))
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo([
                'pos-register.view',
                'pos-register.create',
                'pos-register.edit',
                'pos-register.delete',
                'pos.register.cash-movement.manage',
            ]));
    }
};
