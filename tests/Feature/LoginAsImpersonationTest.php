<?php

namespace Tests\Feature;

use App\Enums\RoleNames;
use App\Models\Business;
use App\Models\Package;
use App\Models\Role;
use App\Models\User;
use App\Services\Concrete\Admin\SubscriptionService;
use App\Services\Concrete\Admin\UserService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Covers the Super Admin "Login As" impersonation feature -
 * UserService::loginAs()/returnToSuperAdmin(), gated by the user.login-as
 * permission (PermissionRegistry, is_system = true) and enforced in
 * UserController via `permission:user.login-as` middleware.
 */
class LoginAsImpersonationTest extends TestCase
{
    use DatabaseTransactions;

    private function makeSuperAdmin(string $email): User
    {
        $role = Role::whereNull('business_id')->where('name', RoleNames::SUPERADMIN)->first();

        $user = User::create([
            'name' => 'Impersonation Super Admin', 'email' => $email,
            'password' => bcrypt('password'), 'business_id' => null, 'status' => 'active',
            'is_deleted' => 0, 'date_created' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_super_admin_can_login_as_a_business_admin_and_return(): void
    {
        $business = Business::create([
            'business_id' => generateUuid(),
            'name' => 'Impersonation Test Co',
            'owner_name' => 'Tester',
            'owner_email' => 'impersonation-owner@test.local',
            'owner_phone' => '0000000000',
            'code' => 'IMPTEST',
            'email' => 'impersonation@test.local',
            'status' => 'active',
            'is_deleted' => 0,
            'date_created' => now(),
        ]);

        $businessAdminRole = Role::whereNull('business_id')->where('name', RoleNames::BUSINESSADMIN)->first();

        $superAdmin = $this->makeSuperAdmin('impersonation-sa@test.local');

        $businessAdmin = User::create([
            'name' => 'Impersonation Business Admin', 'email' => 'impersonation-ba@test.local',
            'password' => bcrypt('password'), 'business_id' => $business->business_id, 'status' => 'active',
            'is_deleted' => 0, 'date_created' => now(),
        ]);
        $businessAdmin->assignRole($businessAdminRole);

        $this->actingAs($superAdmin);

        app(UserService::class)->loginAs($businessAdmin->id);

        $this->assertEquals($businessAdmin->id, Auth::id(), 'loginAs() should switch the session to the target user.');
        $this->assertEquals($superAdmin->id, session('impersonator_id'), 'The original Super Admin id must be stashed in the session.');

        $restored = app(UserService::class)->returnToSuperAdmin();

        $this->assertTrue($restored);
        $this->assertEquals($superAdmin->id, Auth::id(), 'returnToSuperAdmin() should restore the original Super Admin session.');
        $this->assertNull(session('impersonator_id'), 'impersonator_id must be cleared after returning.');
    }

    public function test_login_as_rejects_logging_in_as_self(): void
    {
        $superAdmin = $this->makeSuperAdmin('self-login-sa@test.local');

        $this->actingAs($superAdmin);

        $this->expectException(Exception::class);
        app(UserService::class)->loginAs($superAdmin->id);
    }

    public function test_return_to_super_admin_without_impersonating_is_a_noop(): void
    {
        $this->assertFalse(app(UserService::class)->returnToSuperAdmin());
    }

    public function test_business_admin_cannot_hit_the_login_as_route(): void
    {
        $business = Business::create([
            'business_id' => generateUuid(),
            'name' => 'Impersonation Guard Test Co',
            'owner_name' => 'Tester',
            'owner_email' => 'impersonation-guard-owner@test.local',
            'owner_phone' => '0000000000',
            'code' => 'IMPGUARD',
            'email' => 'impersonation-guard@test.local',
            'status' => 'active',
            'is_deleted' => 0,
            'date_created' => now(),
        ]);

        // An active subscription so check.subscription doesn't redirect
        // before the permission middleware ever runs (mirrors
        // DeletePermissionLockdownTest::test_super_admin_can_delete...).
        $package = Package::where('name', 'Business')->where('duration_type', 'monthly')->firstOrFail();
        app(SubscriptionService::class)->createInitial($business, $package, ['mark_paid' => true]);
        $business->refresh();

        $businessAdminRole = Role::whereNull('business_id')->where('name', RoleNames::BUSINESSADMIN)->first();

        $businessAdmin = User::create([
            'name' => 'Guard Business Admin', 'email' => 'impersonation-guard-ba@test.local',
            'password' => bcrypt('password'), 'business_id' => $business->business_id, 'status' => 'active',
            'is_deleted' => 0, 'date_created' => now(),
        ]);
        $businessAdmin->assignRole($businessAdminRole);

        $otherStaff = User::create([
            'name' => 'Other Staff', 'email' => 'impersonation-guard-staff@test.local',
            'password' => bcrypt('password'), 'business_id' => $business->business_id, 'status' => 'active',
            'is_deleted' => 0, 'date_created' => now(),
        ]);

        // A Business Admin never holds user.login-as (is_system = true is
        // stripped from every non-Super-Admin role by RoleService), so the
        // controller's permission:user.login-as middleware must reject this.
        $this->actingAs($businessAdmin)
            ->get(route('users.login-as', $otherStaff->id))
            ->assertForbidden();
    }
}
