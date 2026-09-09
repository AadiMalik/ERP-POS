<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Converts order_types/order_sources from per-business lookup tables
     * into global platform master data (Super Admin managed, like Package).
     * For each table: dedupes rows by `code` (keeping the earliest-created
     * row per code as canonical), repoints every place that stores the
     * winning table's id onto the canonical id, drops the losing duplicate
     * rows, then drops the now-unused `business_id` column. Also strips the
     * order-type.% / order-source.% permissions from every non-Super-Admin
     * role, same pattern as 2026_09_09_090300_strip_delete_permissions_from_non_super_admin_roles.php.
     *
     * @return void
     */
    public function up()
    {
        $this->deduplicateAndGoGlobal(
            table: 'order_types',
            pkColumn: 'order_type_id',
            referencingColumns: [
                'orders' => ['order_type_id'],
                'pos_settings' => ['default_order_type_id'],
                'voucher_order_types' => ['order_type_id'],
            ]
        );

        $this->deduplicateAndGoGlobal(
            table: 'order_sources',
            pkColumn: 'order_source_id',
            referencingColumns: [
                'orders' => ['order_source_id'],
                'pos_settings' => ['default_order_source_id'],
                'voucher_order_sources' => ['order_source_id'],
            ]
        );

        $this->stripPermissionsFromNonSuperAdminRoles(['order-type.%', 'order-source.%']);
    }

    private function deduplicateAndGoGlobal(string $table, string $pkColumn, array $referencingColumns): void
    {
        $rows = DB::table($table)->orderBy('date_created')->get();

        $canonicalIdByCode = [];
        $duplicateToCanonicalId = [];

        foreach ($rows as $row) {
            $code = $row->code;
            if (!isset($canonicalIdByCode[$code])) {
                $canonicalIdByCode[$code] = $row->{$pkColumn};
                continue;
            }
            $duplicateToCanonicalId[$row->{$pkColumn}] = $canonicalIdByCode[$code];
        }

        foreach ($duplicateToCanonicalId as $duplicateId => $canonicalId) {
            foreach ($referencingColumns as $refTable => $columns) {
                if (!Schema::hasTable($refTable)) {
                    continue;
                }
                foreach ($columns as $column) {
                    DB::table($refTable)->where($column, $duplicateId)->update([$column => $canonicalId]);
                }
            }

            DB::table($table)->where($pkColumn, $duplicateId)->delete();
        }

        Schema::table($table, function (Blueprint $tableBlueprint) {
            $tableBlueprint->dropColumn('business_id');
        });
    }

    private function stripPermissionsFromNonSuperAdminRoles(array $namePatterns): void
    {
        $query = DB::table('permissions');
        foreach ($namePatterns as $index => $pattern) {
            $index === 0
                ? $query->where('name', 'like', $pattern)
                : $query->orWhere('name', 'like', $pattern);
        }
        $permissionIds = $query->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        $superAdminRoleIds = DB::table('roles')
            ->whereNull('business_id')
            ->where('name', 'Super Admin')
            ->pluck('id');

        DB::table('role_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->whereNotIn('role_id', $superAdminRoleIds)
            ->delete();

        $superAdminUserIds = DB::table('model_has_roles')
            ->whereIn('role_id', $superAdminRoleIds)
            ->where('model_type', 'App\\Models\\User')
            ->pluck('model_id');

        DB::table('model_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->where('model_type', 'App\\Models\\User')
            ->whereNotIn('model_id', $superAdminUserIds)
            ->delete();
    }

    /**
     * Irreversible by design - which business originally owned a merged
     * duplicate row, and which non-Super-Admin roles held the stripped
     * permissions, is not recorded anywhere to restore.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
