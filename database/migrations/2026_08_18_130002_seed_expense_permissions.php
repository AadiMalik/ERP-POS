<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permission records for the Expense Management module, seeded as plain
     * rows in the existing `permissions` table like every other module - see
     * database/migrations/2026_08_13_090020_seed_order_pos_permissions.php.
     *
     * @return void
     */
    public static function permissions(): array
    {
        return [
            'expense.access' => 'Record POS Expense',
            'expense.view' => 'View Expense Details',
            'expense.create' => 'Create Expense',
            'expense.edit' => 'Edit Expense',
            'expense.post' => 'Post/Unpost Expense',
            'expense.delete' => 'Delete Expense',
            'expense.report.view' => 'View Expense Report',
            'expense-category.manage' => 'Manage Expense Categories',
            'admin-expense.manage' => 'Manage Admin Expenses',
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
