<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permission records for the Audit & Security module (Phase 1 of the
     * Notifications, Automation, Audit & Security system), seeded as plain
     * rows in the existing `permissions` table like every other module - see
     * database/migrations/2026_08_18_130002_seed_expense_permissions.php.
     *
     * @return void
     */
    public static function permissions(): array
    {
        return [
            'activity-log.view'   => 'View Activity/Audit Log',
            'login-history.view'  => 'View Login History',
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
