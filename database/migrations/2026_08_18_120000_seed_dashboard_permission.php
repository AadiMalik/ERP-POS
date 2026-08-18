<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permission gating the Business Dashboard for POS-facing roles (Order
     * Taker, POS Manager) - seeded as a plain row in the existing
     * `permissions` table like every other module, following the same
     * pattern as seed_order_pos_permissions.php. Not auto-assigned to any
     * role; a Business Admin grants it via the Role edit screen.
     *
     * @return void
     */
    public static function permissions(): array
    {
        return [
            'dashboard.view' => 'View Business Dashboard',
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
