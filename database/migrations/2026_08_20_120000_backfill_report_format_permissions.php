<?php

use App\Models\Role;
use App\Support\Permissions\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * One-time backfill for the new per-format report permissions
 * (`reports.<slug>.print` / `.pdf` / `.export` / `.export-csv`), introduced
 * alongside the existing `reports.<slug>.view` so report output formats can
 * be restricted independently (see PermissionRegistry's report modules).
 *
 * Creates the new permission rows (mirrors PermissionSeeder's upsert, needed
 * here so this migration works regardless of whether `db:seed
 * --class=PermissionSeeder` has been re-run yet) and, for every existing
 * role that already holds a report's `.view` permission, grants that role
 * the new format permissions too - so existing users don't lose PDF/Excel/
 * CSV/Print access the moment this ships. This is deliberately a one-time
 * historical correction, not logic that re-runs on every seed: after this,
 * admins are expected to independently manage `.view` vs `.print` vs `.pdf`
 * vs `.export` vs `.export-csv` per role, and a repeatable "re-grant on every
 * seed" step would silently undo any such customization.
 */
return new class extends Migration
{
    public function up()
    {
        $flat = PermissionRegistry::flat();
        $now = now();

        $permissionRows = [];
        foreach ($flat as $name => $meta) {
            $permissionRows[] = [
                'name' => $name,
                'guard_name' => 'web',
                'is_system' => $meta['is_system'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($permissionRows, 200) as $chunk) {
            DB::table('permissions')->upsert(
                $chunk,
                ['name', 'guard_name'],
                ['is_system', 'updated_at']
            );
        }

        // Spatie caches permission lookups (Permission::findByName(), used by
        // givePermissionTo() below) - the rows just inserted via the raw
        // query builder above wouldn't be visible to it otherwise.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Map every reports.<slug>.view name to whichever of its
        // print/pdf/export/export-csv siblings actually exist in the
        // registry (some legacy report-style permissions predate this
        // format split and may not have every sibling - e.g. none should be
        // skipped today, but this stays correct if that ever changes).
        $viewToSiblings = [];
        foreach (array_keys($flat) as $name) {
            if (!str_starts_with($name, 'reports.') || !str_ends_with($name, '.view')) {
                continue;
            }
            $slug = substr($name, strlen('reports.'), -strlen('.view'));
            $siblings = [];
            foreach (['print', 'pdf', 'export', 'export-csv'] as $format) {
                $siblingName = "reports.{$slug}.{$format}";
                if (isset($flat[$siblingName])) {
                    $siblings[] = $siblingName;
                }
            }
            if ($siblings) {
                $viewToSiblings[$name] = $siblings;
            }
        }

        if (!$viewToSiblings) {
            return;
        }

        Role::with('permissions')->chunkById(200, function ($roles) use ($viewToSiblings) {
            foreach ($roles as $role) {
                $currentNames = $role->permissions->pluck('name')->all();
                $toGrant = [];

                foreach ($viewToSiblings as $viewName => $siblings) {
                    if (!in_array($viewName, $currentNames, true)) {
                        continue;
                    }
                    foreach ($siblings as $sibling) {
                        if (!in_array($sibling, $currentNames, true)) {
                            $toGrant[] = $sibling;
                        }
                    }
                }

                if ($toGrant) {
                    $role->givePermissionTo(array_unique($toGrant));
                }
            }
        }, 'id');
    }

    public function down()
    {
        // Intentionally irreversible - there is no reliable way to
        // distinguish permissions this backfill granted from ones a
        // Super Admin/Business Admin has since granted or revoked by hand.
    }
};
