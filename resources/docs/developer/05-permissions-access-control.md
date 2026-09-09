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
  (raw Permission CRUD, Package, Business, Subscription/Billing,
  `business-access-control`, `system-feature-flag`). Everything a
  Business Admin should be able to manage defaults to `false`.
- Permission **names, once shipped, are permanent** — never rename or repurpose one;
  add a new one instead.
- Helper methods: `allNames()` (every permission), `businessNames()` (every
  non-system permission — what Business Admin gets), `namesForModules($keys)`,
  `namesForModulesExcludingActions($keys, $exclude)`, and `operationalModuleKeys()`
  (the list of business/branch-scoped module keys used to build broad "manager"
  role defaults without hand-listing every permission).
- Column-level DataTable gates (extra financial fields) use
  `customer.view-financial` and `supplier.view-financial`. See
  [Centralized DataTable System](24-datatable-system.md).

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

## Global Delete-Permission Lockdown

Only the **true global Super Admin role** (`business_id === null && name ===
RoleNames::SUPERADMIN`) may ever hold a `*.delete` permission. No Business
Admin, Business Owner, Manager, Employee, or any other business-level role —
including a custom tenant role a Super Admin creates on a business's behalf —
may have delete on any module. This is enforced at three layers, not just one:

1. **Data**: `PermissionRegistry::businessNames()` (feeding the global
   Business Admin template) and every role template in
   `RoleDefaultPermissions` never include a `.delete` name —
   `RoleDefaultPermissions::defaultsForRole()` wraps every non-Super-Admin
   case in `PermissionRegistry::withoutDeleteActions()`, so a future role
   case can't accidentally reintroduce delete by forgetting to call
   `namesForModulesExcludingActions([...], ['delete'])` itself.
2. **UI**: `PermissionRegistry::grouped($businessAdminOnly, $enabledModuleKeys, $includeDeleteActions)`
   strips every `delete` action key unless `$includeDeleteActions` is true —
   `RoleController::create()` always passes `false` (a new role is never the
   global Super Admin role); `edit($id)` computes it from the loaded role
   (`is_null($role->business_id) && $role->name === RoleNames::SUPERADMIN`).
   Delete checkboxes are never rendered for any other role, anywhere.
3. **Backstop (the one that actually matters)**: `RoleService::syncPermissions()`
   — the single method every permission-assignment path routes through (the
   Role Create/Edit form, `resetBusinessRoles()`, and any future API) — strips
   every `.delete` name from the incoming array unless the target role is the
   true global Super Admin role. A spoofed `permissions[]=branch.delete` in a
   raw POST, or a `.delete` name manually added to any payload, is silently
   dropped here regardless of what the UI showed. This is the layer that
   makes 1 and 2 non-bypassable rather than just cosmetic.

Existing `permission:{module}.delete` middleware on `destroy()` actions
(93 controllers) is unchanged — no per-controller edits were needed. Once no
non-Super-Admin role can ever hold that permission, Spatie's own middleware
check already blocks every one of those routes for everyone except Super
Admin; the fix is entirely upstream. See
`tests/Feature/DeletePermissionLockdownTest.php` for the verifying tests.

Deactivate/archive endpoints (where a module has them) are a **separate**
concept from delete and are untouched by this rule — they remain available to
whichever role has the relevant `.edit`/`.status` permission. Where a module
has no deactivate/archive action, the spec's intended fallback is that the
business contacts the Super Admin for a permanent removal — this rule does
not add new archive UI to modules that don't already have one.

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
