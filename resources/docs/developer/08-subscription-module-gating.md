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

Public plans are seeded by `database/seeders/IntroPackageCatalogSeeder`:
**Starter**, **Growth**, **Business**, and **Enterprise**, each as a separate
**monthly** and **yearly** row (`duration_type`). Unique key is
`name + duration_type` among non-deleted packages.

- **List price** (`packages.price`): monthly amount, or yearly annual total
  (`monthly × 12` for yearly rows).
- **Discount %** (`packages.discount`): applied to list price when charging /
  displaying the effective amount. Yearly catalog rows seed with **10%**;
  monthly rows seed with **0%** (discount can still be set later for monthly).
- **Effective charge**: `price × (1 − discount/100)` via `Package::effectivePrice()`
  / `priceForCycle()`.
- Module access and numeric caps live in `package_modules` (not marketing
  features / limitations / compare JSON — those admin fields were removed).

`PackageSeeder` only deactivates legacy **Professional** / **Basic Plan** rows.
Run `php artisan db:seed --class=IntroPackageCatalogSeeder` after migrating
`discount`.

Business Admin **My Subscription** filters pricing cards by Monthly/Yearly
toggle; Upgrade/Downgrade/Renewal uses the selected package’s `duration_type`
as `requested_billing_cycle`.

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

## Dashboard widget gating

The Business Dashboard (`HomeController` → `DashboardAccessService` /
`DashboardService`) layers package modules on top of role tiers:

- **Finance widgets** (Net Profit KPI, Revenue/Expenses/Profit overview,
  Account/COA summary, receivables/payables, ledger activity, recent payments)
  require a role in `DashboardAccessService::FINANCE_ROLES` **and**
  `businessModuleEnabled('accounting')`. Without Accounting on the package,
  those widgets are omitted even for Business Admin.
- Sidebar Accounting / HRM headers and `@canAccess` quick links already use
  the same `FeatureLimitService::hasModule` / `AccessControlService` checks.

## Platform-Level Gate

`superadmin` middleware (`App\Http\Middleware\EnsureSuperAdmin`) is a **separate**
concept from module gating — it protects the platform operator's own screens
(managing tenant Businesses, Packages, Subscriptions/Billing) rather than a
per-tenant feature toggle.

## Unified Subscription Invoices Queue

Super Admin billing review is centered on `subscription_invoices` (not a
separate request table):

| Column / concept | Purpose |
|------------------|---------|
| `request_type` | `new` (intro / contact register / unpaid createInitial) or `renew` (self-service / admin renew unpaid) |
| Unpaid invoice + pending `subscription_payments` row | Created immediately when `mark_paid` / `payment.confirm` is false |
| Payment confirm | `PaymentService::approve` — invoice paid, subscription `active`, business `active`, `subscription_start`/`end` applied, confirmation email + PDF via platform `EmailService`, linked renewal request → approved |
| Payment reject | Final; cannot confirm afterwards (and confirmed cannot be rejected) |
| Soft delete invoice | Cascades soft-delete of payments; cancels `payment_pending` subscription |

**Expiry freeze until confirm:** unpaid `createInitial` sets business `pending`
and null dates; unpaid `renew` leaves previous `subscription_end` /
`current_business_subscription_id` untouched.

**Business statuses:** `active`, `suspended`, `expired`, plus `pending` and
`under_review`.

**Restricted access:** `CheckBusinessSubscription` + sidebar — when
`SubscriptionService::isAccessRestricted()` (pending / under_review / expired),
non–Super Admin users only reach allowlisted My Subscription / profile /
logout routes.

**Notifications:** unpaid invoice create dispatches in-app
`subscription_payment_pending` to Super Admins; sidebar Invoices badge uses
`InvoiceService::pendingPaymentCount()`.

**Intro Contact → register:** `ContactInquiryService::registerBusiness` /
`activateBusiness` / `updatePayment` reuse `BusinessRegistrationService` and
`PaymentService`.

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
