<?php

namespace Database\Seeders;

use App\Enums\RoleNames;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Bootstraps the very first Super Admin login for a fresh/emptied `users`
 * table. Assigning the global "Super Admin" role is sufficient for full
 * access - PermissionSeeder syncs every registered permission onto that role
 * (see RoleDefaultPermissions::defaultsForRole(SUPERADMIN) =>
 * PermissionRegistry::allNames()), so no direct permission assignment is
 * needed here. Idempotent - safe to re-run.
 */
class FirstUser extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('1234'),
                'business_id' => null,
                'status' => 'active',
                'must_change_password' => false,
                'date_created' => now(),
            ]
        );

        if (!$user->hasRole(RoleNames::SUPERADMIN)) {
            $user->assignRole(RoleNames::SUPERADMIN);
        }
    }
}
