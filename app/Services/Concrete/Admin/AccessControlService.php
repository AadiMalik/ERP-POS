<?php

namespace App\Services\Concrete\Admin;

use App\Support\Permissions\PermissionRegistry;
use App\Support\Subscription\SubscriptionModuleRegistry;
use Illuminate\Support\Facades\Auth;

/**
 * Single call site combining the two independent access-control systems -
 * Spatie user permissions (PermissionRegistry) and business/package module
 * gating (SubscriptionModuleRegistry + FeatureLimitService) - for view-layer
 * decisions (sidebar, dashboard, global search, report buttons) that need
 * both checks together. Route/controller-level enforcement is untouched and
 * still uses `permission:` and `module:` middleware directly per CLAUDE.md.
 */
class AccessControlService
{
    /**
     * Maps every registered permission name to the PermissionRegistry module
     * group key it belongs to (built once per request). This is looked up
     * via the group key rather than parsed from the permission string
     * because several permissions sharing the same name prefix belong to
     * different package modules - e.g. `reports.employee-master-report.view`
     * (group `hrm-reports`, parented to `hrm`) vs `reports.account-ledger.view`
     * (group `reports`, a core/ungated module) both start with `reports.`.
     */
    private ?array $permissionModuleMap = null;

    public function allows(string $permission, ?string $moduleKey = null): bool
    {
        $user = Auth::user();

        if (!$user || !$user->can($permission)) {
            return false;
        }

        $moduleKey = $moduleKey ?? $this->moduleKeyForPermission($permission);

        if ($moduleKey === null || !SubscriptionModuleRegistry::find($moduleKey)) {
            return true;
        }

        return app(FeatureLimitService::class)->hasModule($moduleKey);
    }

    public function allowsAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->allows($permission)) {
                return true;
            }
        }

        return false;
    }

    private function moduleKeyForPermission(string $permission): ?string
    {
        if ($this->permissionModuleMap === null) {
            $map = [];

            foreach (PermissionRegistry::modules() as $groupKey => $group) {
                foreach ($group['actions'] as $action) {
                    $map[$action['name']] = $groupKey;
                }
            }

            $this->permissionModuleMap = $map;
        }

        return $this->permissionModuleMap[$permission] ?? null;
    }
}
