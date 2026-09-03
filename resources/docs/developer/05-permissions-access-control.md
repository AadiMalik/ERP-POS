# Permissions & Access Control System

This is the system CLAUDE.md refers to as **mandatory** for every module — read
this alongside the project's `CLAUDE.md` → "Permissions & Access Control" section,
which this page documents in more detail.

## The Registry

`App\Support\Permissions\PermissionRegistry` is the **single source of truth** for
every permission in the system, grouped by module:
```php
public static function modules(): array
{
    return [
        'warehouse' => ['label' => 'Warehouse', 'actions' => [
            'view' => ['name' => 'warehouse.view', 'label' => 'View', 'is_system' => false],
            // ...
        ]],
        // ...
    ];
}
```
- `is_system = true` is reserved for platform-level, Super-Admin-only actions
  (raw Permission CRUD, Package, Business, Subscription/Billing). Everything a
  Business Admin should be able to manage defaults to `false`.
- Permission **names, once shipped, are permanent** — never rename or repurpose one;
  add a new one instead.
- Helper methods: `allNames()` (every permission), `businessNames()` (every
  non-system permission — what Business Admin gets), `namesForModules($keys)`,
  `namesForModulesExcludingActions($keys, $exclude)`, and `operationalModuleKeys()`
  (the list of business/branch-scoped module keys used to build broad "manager"
  role defaults without hand-listing every permission).

## Role Defaults

`App\Support\Permissions\RoleDefaultPermissions::defaultsForRole($roleName)` maps
each `App\Enums\RoleNames` case to a starting permission set built from the
Registry's helpers (e.g. Super Admin → `allNames()`, Business Admin →
`businessNames()`, a manager role → `namesForModulesExcludingActions(operationalModuleKeys(), ['delete'])`).
These are **starting points** — editable per-role afterwards via the Role
Create/Edit screen, never hard limits.

## Syncing to the Database

`database/seeders/PermissionSeeder` reads the Registry + Role Defaults and syncs
permission rows. It fully re-syncs the two **global role templates** (Super Admin,
Business Admin) on every run; it does **not** touch business-scoped custom roles
(those are managed via `RoleService::resetBusinessRoles()` per-tenant). Run it after
any Registry change:
```bash
php artisan db:seed --class=PermissionSeeder
```

## Enforcing at the Controller

Constructor-level middleware, scoped with `->only([...])`:
```php
$this->middleware('permission:warehouse.view')->only(['index', 'getData', 'byBusiness', 'byBranch']);
$this->middleware('permission:warehouse.create')->only(['create']);
$this->middleware('permission:warehouse.create|warehouse.edit')->only(['store']); // one action serving both create & edit
$this->middleware('permission:warehouse.edit')->only(['edit']);
$this->middleware('permission:warehouse.delete')->only(['destroy']);
```
Single-permission controllers (e.g. `ActivityLogController`) just call
`$this->middleware('permission:activity-log.view');` with no `->only()` when every
action needs the same permission. **This must never be skipped** — frontend hiding
is a UX layer only, the middleware is the actual security boundary.

## Enforcing in Blade

Two custom directives, registered in `App\Providers\AppServiceProvider::boot()`
and backed by the `AccessControlService` singleton (built once per request):
```blade
@canAccess('documentation.view')
    ...
@endcanAccess

@canAccessAny(['ess.dashboard.view', 'ess.attendance.manage'])
    ...
@endcanAccessAny
```
`@canAccess('permission.name', 'optional-module-key')` checks the raw permission
**and**, if a module key is passed, the subscription-tier module gate in one call —
use this form for anything that also needs a `module:` check.

## Checklist for a New Module

1. Add its permissions to `PermissionRegistry` (grouped under a new or existing
   module key).
2. If a role should get it by default, add it to `RoleDefaultPermissions` (or, more
   often, add the module key to `operationalModuleKeys()` so it's picked up
   automatically by the broad manager roles).
3. Run `php artisan db:seed --class=PermissionSeeder`.
4. Gate every controller action with constructor-level `permission:` middleware.
5. Wrap any new sidebar entry in `@can`/`@canAccess`.
6. **Update the documentation** — see
   [The Documentation System Itself](12-documentation-system.md) and CLAUDE.md's
   "Documentation" section.

## Branch-scoped single-record authorization (sanctioned exception)

`applyRoleScope()` (`app/Helpers/CommonFunctions.php`) scopes a **query** to what
a role should see (Super Admin: unrestricted; business-level roles: their
business; branch-level and branch-anchored mixed roles: their business **and**
branch). Some actions authorize one already-loaded **record** instead — e.g.
"close this specific register session" — where a query scope doesn't apply.
For that case use the record-level counterpart,
`userInBusinessBranchScope($user, $business_id, $branch_id)`, which applies the
exact same role groupings to a single record's business/branch pair. The
canonical pattern (see the POS Register/Session module in
[Modules, Controllers & Services](03-modules-controllers-services.md)) is: the
record's owner may always act on their own record with no extra permission,
and anyone else needs both to be in scope of the record's business/branch
**and** to hold the specific permission for that action.

## Context-dependent permission checks (sanctioned exception)

Constructor `->only([...])` middleware cannot express "this permission applies
*only if* a specific field is present on this request." In those cases the
controller may check inline with `Auth::user()->can('…')` (or Spatie's
`$user->can(...)`) after validating the payload, and reject with 403 when the
optional field is present but the user lacks the permission.

**Canonical example:** `OrderController` — discount / coupon / price-override
fields are gated only when those fields appear on the save/post request.
Do **not** move those checks into constructor middleware; documenting the
inline check next to the field is the approved pattern. Everything else still
belongs in constructor middleware.
