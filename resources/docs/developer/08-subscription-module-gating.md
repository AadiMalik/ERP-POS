# Subscription & Module Gating

This is the SaaS layer that lets one codebase serve many tenant businesses on
different plans.

## The Chain

`Package` (a plan definition, `hasMany PackageModule`) → a `Business` has a current
`BusinessSubscription` pointing at a `Package` → `package_modules` is the actual
enable/limit matrix (per package, per module key: enabled? what numeric limit?).

`App\Support\Subscription\SubscriptionModuleRegistry` is the static catalogue of
every module key the system knows about, each with a `type`
(`core` | `feature` | `limited`) and an optional `parent` (for sub-flags nested
under an umbrella module).

`App\Services\Concrete\Admin\FeatureLimitService::hasModule(string $module, ?Business $business = null): bool`:
```php
if (getRoleName() == RoleNames::SUPERADMIN) return true;          // Super Admin bypasses everything
$meta = SubscriptionModuleRegistry::find($module);
if ($meta && $meta['type'] === 'core') return true;                 // core keys are always on
$business = $business ?? Auth::user()->business;
$business->loadMissing('package.modules');
// ...checks $business->package->modules against the module key
```
`FeatureLimitService::check()` uses the same registry for **numeric caps** (e.g.
max 5 warehouses on a given plan) at the record level, separate from the route-group
gate.

`FeatureLimitService::compareToPackage(Business $business, Package $target)`
compares current usage (`resolveCount` / `usageByLimitedKey`) against a
**target** package's `package_modules` rows. For every `limited` registry key:

- disabled on the target (or parent umbrella off) and `used > 0` → blocker
  (treated as limit 0)
- enabled, not unlimited, and `used > limit_value` → blocker

Each blocker is `{key, label, used, allowed, excess}`.
`formatCompareBlockersMessage()` / `assertCompatibleWithPackage()` turn that
into the “reduce these first, then you can change plans” error. Same-package
renewal skips the check. A plan change (Business Admin request **and** Super
Admin `SubscriptionService::renew()` when `package_id` differs) is rejected
until usage fits.

## Catalog packages

`database/seeders/PackageSeeder` upserts three public plans by name (Starter
$49, Professional $149, Enterprise $349) and writes `package_modules` the same
way `PackageService::saveModules()` does. It does **not** delete an existing
plan such as Basic Plan. Run `php artisan db:seed --class=PackageSeeder`.
Business Admin **My Subscription** renders those (plus the tenant’s current
package even if inactive) as pricing cards; Upgrade/Downgrade submits the
existing `my-subscription.renewal-requests.store` request after the
compatibility check.

## Route-Level Gate

`App\Http\Middleware\EnsureModuleEnabled` (registered as the `module` middleware
alias in `app/Http/Kernel.php`) wraps a route group:
```php
Route::group(['middleware' => ['module:hrm']], function () { ... });
```
```php
public function handle(Request $request, Closure $next, string $module)
{
    if (!app(FeatureLimitService::class)->hasModule($module)) {
        abort(403, 'This module is not enabled on your subscription package.');
    }
    return $next($request);
}
```
Gating happens **only at the umbrella level** in route groups (`hrm`, `payroll`,
`inventory`, `accounting`, `pos`, `service-management`) — the many finer-grained
"limited" sub-keys in the Registry (e.g. `warehouse`, `employee`, `payslip`) back
per-record numeric limits via `FeatureLimitService::check()`, not their own route
middleware.

## Platform-Level Gate

`superadmin` middleware (`App\Http\Middleware\EnsureSuperAdmin`) is a **separate**
concept from module gating — it protects the platform operator's own screens
(managing tenant Businesses, Packages, Subscriptions/Billing) rather than a
per-tenant feature toggle.

## Subscription Lifecycle

`ProcessSubscriptionLifecycleCommand` (daily, `subscriptions:process-lifecycle`)
advances tenant subscription states (trial → expired, due-renewal reminders).
`SendSubscriptionNotificationJob` dispatches individual lifecycle
notifications; `GenerateSubscriptionInvoicePdfJob` renders and stores invoice
PDFs asynchronously. See [Jobs, Commands & Scheduling](09-jobs-commands.md).

## Adding a New Gated Module

1. Add the module key (and any sub-keys) to `SubscriptionModuleRegistry` with the
   right `type`/`parent`.
2. Add a toggle for it on the Package Create/Edit screen if it should be
   plan-configurable.
3. Wrap its route group in `module:<key>` middleware.
4. Layer `permission:` middleware on top as normal — module gating and permission
   gating are independent and both required.
