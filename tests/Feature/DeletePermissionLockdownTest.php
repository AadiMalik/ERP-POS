<?php

namespace Tests\Feature;

use App\Enums\RoleNames;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Package;
use App\Models\Role;
use App\Models\User;
use App\Services\Concrete\Admin\RoleService;
use App\Services\Concrete\Admin\SubscriptionService;
use App\Support\Permissions\RoleDefaultPermissions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Only the true global Super Admin role may ever hold a `*.delete`
 * permission - see PermissionRegistry::withoutDeleteActions() and its
 * enforcement backstop in RoleService::syncPermissions(). These tests cover
 * the three things that rule promises: no business-level role template ever
 * has delete by default, a spoofed `.delete` name in a raw payload is
 * silently stripped, and Super Admin's own ability to delete is untouched.
 */
class DeletePermissionLockdownTest extends TestCase
{
    use DatabaseTransactions;

    public function test_global_business_admin_role_has_no_delete_permissions(): void
    {
        $businessAdmin = Role::whereNull('business_id')->where('name', RoleNames::BUSINESSADMIN)->first();

        $this->assertNotNull($businessAdmin, 'Global Business Admin role must exist (run PermissionSeeder).');

        $deleteNames = $businessAdmin->permissions()->where('name', 'like', '%.delete')->pluck('name');

        $this->assertCount(0, $deleteNames, 'Business Admin must never hold a .delete permission: ' . $deleteNames->implode(', '));
    }

    public function test_every_business_role_template_default_has_no_delete_permissions(): void
    {
        foreach ([
            RoleNames::BRANCHADMIN, RoleNames::GENERALMANAGER, RoleNames::OPERATIONMANAGER,
            RoleNames::INVENTORYMANAGER, RoleNames::FINANCEMANAGER, RoleNames::SALEMANAGER,
            RoleNames::PURCHASEMANAGER, RoleNames::MARKITINGMANAGER, RoleNames::ACCOUNTANT,
            RoleNames::HRMANAGER, RoleNames::EMPLOYEE, RoleNames::REPORTINGANALYST,
            RoleNames::STAFF, RoleNames::POSMANAGER, RoleNames::ORDERTAKER,
        ] as $roleName) {
            $names = RoleDefaultPermissions::defaultsForRole($roleName);
            $deletes = array_values(array_filter($names, fn ($n) => str_ends_with($n, '.delete')));

            $this->assertCount(0, $deletes, "$roleName must never default to a .delete permission: " . implode(', ', $deletes));
        }
    }

    public function test_super_admin_default_still_has_delete_permissions(): void
    {
        $names = RoleDefaultPermissions::defaultsForRole(RoleNames::SUPERADMIN);
        $deletes = array_values(array_filter($names, fn ($n) => str_ends_with($n, '.delete')));

        $this->assertNotEmpty($deletes, 'Super Admin must retain delete capability.');
    }

    public function test_spoofed_delete_permission_is_stripped_for_a_business_scoped_role(): void
    {
        $business = Business::create([
            'business_id' => generateUuid(),
            'name' => 'Lockdown Test Co',
            'owner_name' => 'Tester',
            'owner_email' => 'lockdown-owner@test.local',
            'owner_phone' => '0000000000',
            'code' => 'LOCKTEST',
            'email' => 'lockdown@test.local',
            'status' => 'active',
            'is_deleted' => 0,
            'date_created' => now(),
        ]);

        $role = Role::create([
            'name' => 'Spoofed Delete Role',
            'guard_name' => 'web',
            'business_id' => $business->business_id,
            'description' => 'test',
        ]);

        app(RoleService::class)->syncPermissions($role, ['branch.view', 'branch.delete', 'product.delete']);

        $role->refresh();
        $names = $role->permissions()->pluck('name')->all();

        $this->assertContains('branch.view', $names);
        $this->assertNotContains('branch.delete', $names);
        $this->assertNotContains('product.delete', $names);
    }

    public function test_super_admin_can_delete_a_branch_while_business_admin_cannot(): void
    {
        $business = Business::create([
            'business_id' => generateUuid(),
            'name' => 'Lockdown HTTP Test Co',
            'owner_name' => 'Tester',
            'owner_email' => 'lockdown-http-owner@test.local',
            'owner_phone' => '0000000000',
            'code' => 'LOCKHTTP',
            'email' => 'lockdown-http@test.local',
            'status' => 'active',
            'is_deleted' => 0,
            'date_created' => now(),
        ]);

        // Give the business a real active subscription so the only thing
        // blocking the Business Admin's delete attempt is the missing
        // .delete permission, not an unrelated subscription-gate redirect.
        $package = Package::where('name', 'Business')->where('duration_type', 'monthly')->firstOrFail();
        app(SubscriptionService::class)->createInitial($business, $package, ['mark_paid' => true]);
        $business->refresh();

        $superAdminRole = Role::whereNull('business_id')->where('name', RoleNames::SUPERADMIN)->first();
        $businessAdminRole = Role::whereNull('business_id')->where('name', RoleNames::BUSINESSADMIN)->first();

        $superAdmin = User::create([
            'name' => 'Lockdown Super Admin', 'email' => 'lockdown-sa@test.local',
            'password' => bcrypt('password'), 'business_id' => null, 'status' => 'active',
            'is_deleted' => 0, 'date_created' => now(),
        ]);
        $superAdmin->assignRole($superAdminRole);

        $businessAdmin = User::create([
            'name' => 'Lockdown Business Admin', 'email' => 'lockdown-ba@test.local',
            'password' => bcrypt('password'), 'business_id' => $business->business_id, 'status' => 'active',
            'is_deleted' => 0, 'date_created' => now(),
        ]);
        $businessAdmin->assignRole($businessAdminRole);

        $branchForSuperAdmin = Branch::create([
            'branch_id' => generateUuid(), 'business_id' => $business->business_id,
            'code' => 'BR-SA', 'name' => 'Branch SA', 'status' => 'active',
            'is_deleted' => 0, 'date_created' => now(),
        ]);
        $branchForBusinessAdmin = Branch::create([
            'branch_id' => generateUuid(), 'business_id' => $business->business_id,
            'code' => 'BR-BA', 'name' => 'Branch BA', 'status' => 'active',
            'is_deleted' => 0, 'date_created' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('branch.destroy', $branchForSuperAdmin->branch_id))
            ->assertOk()
            ->assertJson(['Success' => true]);

        $branchForSuperAdmin->refresh();
        $this->assertEquals(1, $branchForSuperAdmin->is_deleted, 'Super Admin delete should soft-delete the branch.');

        $this->actingAs($businessAdmin)
            ->delete(route('branch.destroy', $branchForBusinessAdmin->branch_id))
            ->assertForbidden();

        $branchForBusinessAdmin->refresh();
        $this->assertEquals(0, $branchForBusinessAdmin->is_deleted, 'Business Admin must never be able to delete a branch.');
    }
}
