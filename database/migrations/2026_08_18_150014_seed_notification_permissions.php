<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permission records for the Notification & Alert module (Phase 2 of the
     * Notifications, Automation, Audit & Security system), seeded the same
     * way as every other module - see
     * database/migrations/2026_08_18_150002_seed_audit_security_permissions.php.
     *
     * @return void
     */
    public static function permissions(): array
    {
        return [
            'notification.view'           => 'View Notifications',
            'notification-setting.manage' => 'Manage Notification Settings',
        ];
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (self::permissions() as $name => $label) {
            $exists = DB::table('permissions')
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->exists();

            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'is_system_only' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('permissions')
            ->whereIn('name', array_keys(self::permissions()))
            ->where('guard_name', 'web')
            ->delete();
    }
};
