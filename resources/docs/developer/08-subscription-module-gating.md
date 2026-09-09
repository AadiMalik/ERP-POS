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

### Limit Types & Monthly Usage Periods

Every `limited` registry entry also carries a `limit_type`
(`package_modules.limit_type`), which decides **how** `FeatureLimitService::resolveCount()`
counts usage:

- **`active_count`** / **`configuration_count`** — a plain live-row count
  (`is_deleted = 0`), unaffected by billing period. Used for
  operational/master resources: `branch`, `user`, `warehouse`, `employee`,
  `department`, `designation`, `shift` (active_count); `account`,
  `payment-method`, `discount`, `voucher`, `order-type`, `payment-gateway`,
  `category`, `brand`, etc. (configuration_count).
- **`monthly_creation`** / **`monthly_transaction`** — every row whose
  `date_created` falls inside the business's **current billing period**
  counts, **including soft-deleted, cancelled, void, and returned rows** —
  status changes and soft-deletes never free up quota. Used for
  `customer`, `supplier`, `product`, `order`, `order-return`, `purchase`,
  `journal-entry`, `expense`, `supplier-payment`, `customer-payment`, and
  similar transactional/creation resources. A Super Admin hard-deleting a
  record (see [Permissions & Access Control](05-permissions-access-control.md))
  never reduces this count either, since it only ever counts rows created in
  the window, not current live state.

`FeatureLimitService::currentUsagePeriod(Business $business)` computes that
window: it anchors on the business's active `BusinessSubscription.start_at`
and rolls forward in whole-month increments. This means a **yearly**
subscription still resets its monthly counters every month on its own
anniversary day — it is never granted 12 months of usage at once, and never
resets on the calendar month boundary. Nothing is persisted for this; it's
derived on every call from `start_at` (already tracked).

`check()` returns a response enriched with `resource`, `used`, `limit`,
`period`, and `upgrade_required` alongside `status`/`message`, e.g.:
*"You have reached your monthly Customers limit of 2,000 for the Starter
package. Please upgrade your package to continue."*

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
**Free Trial**, **Starter**, **Business**, **Professional**, and
**Enterprise** — Free Trial is monthly-only (never sold, see below); the four
paid tiers are each seeded as a separate **monthly** and **yearly** row
(`duration_type`). Unique key is `name + duration_type` among non-deleted
packages, so re-running the seeder updates the existing rows in place
(idempotent — safe to run any number of times, never creates duplicates).

- **List price** (`packages.price`): monthly amount, or yearly annual total
  (`monthly × 12` for yearly rows).
- **Discount %** (`packages.discount`): applied to list price when charging /
  displaying the effective amount. Yearly catalog rows seed with **10%**;
  monthly rows seed with **0%**. Current PKR pricing: Starter 5,000/mo
  (54,000/yr), Business 10,000/mo (108,000/yr), Professional 20,000/mo
  (216,000/yr), Enterprise 65,000/mo (702,000/yr).
- **Effective charge**: `price × (1 − discount/100)` via `Package::effectivePrice()`
  / `priceForCycle()`.
- **Setup fee** (`packages.setup_fee`): a one-time onboarding charge, stored
  entirely separately from `price`/`price_yearly` and never read into pricing
  or usage calculations. Free Trial: 0; Starter: 3,000; Business: 5,000;
  Professional: 10,000; Enterprise: 20,000 (framed as "starting from" — custom
  development/data migration/integrations remain separate billable services,
  never bundled into this fee).
- Module access and numeric caps live in `package_modules` (not marketing
  features / limitations / compare JSON — those admin fields were removed).
  Enterprise has `is_unlimited = true` on every `limited` module key with no
  exceptions — only technical infrastructure limits (storage, SMS/WhatsApp/
  payment-provider) apply beyond that, and those are outside this catalog.
- Exposed to the public pricing page via `IntroPublicService::packages()` /
  `mapPackage()`, which already derives monthly/yearly toggle data, the 10%
  yearly-saving (`discount`), and the recommended/unlimited badges
  (`packages.badge`: "Most Popular" on Business, "Unlimited" on Enterprise)
  straight from the catalog — no separate marketing data model needed.

### Free Trial — one-time, non-repeatable

Free Trial (`trial_days = 30`) is assigned automatically at business
registration and is a **complete system-testing package** — every major
umbrella module (inventory, POS, accounting, HRM, payroll, service
management, manufacturing, analytics) is enabled, each with small testing
limits. `businesses.trial_used_at` is set the moment a trial subscription is
created (`SubscriptionService::createInitial()`) and is checked centrally,
before ever assigning a `trial_days > 0` package to a business again, in
both `createInitial()` and `renew()` (the upgrade/downgrade/renewal path) —
so a business can never get a second trial by upgrading, downgrading, or
re-registering, regardless of entry point.

`PackageSeeder` only deactivates legacy **Basic Plan** / **Growth** rows
("Professional" is deliberately excluded from that list — it's now a live
tier name, not a legacy one). Run
`php artisan db:seed --class=IntroPackageCatalogSeeder` after migrating.

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
Gating mostly happens **only at the umbrella level** in route groups (`hrm`,
`payroll`, `inventory`, `accounting`, `pos`, `service-management`) — the many
finer-grained "limited" sub-keys in the Registry (e.g. `warehouse`, `employee`,
`payslip`) back per-record numeric limits via `FeatureLimitService::check()`,
not their own route middleware. Report-type "feature" sub-keys (`hrm-reports`,
`payroll-reports`, `order-reports`) are visibility-gated only — not enabled on
the package means the permission group is stripped from
`PermissionRegistry::grouped()` (via `SubscriptionModuleRegistry::enabledPermissionModuleKeysFor()`)
so no role can be granted it, but there is no dedicated `module:order-reports`
route middleware (same as `hrm-reports`/`payroll-reports`).

`offline-pos` (parent: `pos`) is the one sub-key with its **own** route
middleware, `module:offline-pos`, layered onto `module:pos` on
`routes/offline.php`'s two authenticated groups and checked directly in
`OfflineSetupService::validateBusiness()`/`registerDeviceWithCredentials()` —
because it gates a genuinely separate client (the Electron desktop app), a
downgrade needs to hard-block sync/device-registration immediately rather than
just hide a permission checkbox.

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
5. **Backfill `package_modules` for every existing package** — `Package::moduleEnabled()`
   returns `false` when a package has no row for the key, so a business with an
   otherwise-enabled parent umbrella loses the new sub-feature the instant the
   key is registered, until a row exists. `IntroPackageCatalogSeeder` only
   covers its own 5 named catalog packages; write a small one-off migration
   (see `2026_09_03_070000_backfill_order_reports_offline_pos_bank_reconciliation_modules.php`
   for the pattern) that upserts a row per existing package, inheriting the
   parent's already-synced `is_enabled` state.

## Three Separate Gating Systems — Don't Conflate Them

This file covers only the package/module-tier system above. Two other, unrelated
gating layers exist and are easy to confuse with it:

1. **Package/module gating** (this file) — per-business, tied to the
   business's subscribed `Package`/`package_modules`. Answers "does this
   business's *plan* include this feature?" Middleware: `module:<key>` →
   `EnsureModuleEnabled` → `FeatureLimitService::hasModule()`.
2. **Business Access Control** — per-business, tied directly to 4 boolean
   columns on `businesses` (`erp_access_enabled`, `storefront_access_enabled`,
   `pos_access_enabled`, `offline_pos_access_enabled`), set only by Super
   Admin, independent of package/plan. Answers "has Super Admin blocked this
   business from an entire platform?" Middleware: `platform:<key>` →
   `EnsurePlatformAccess`. See
   [Notifications, Alerts & Access Control](23-notifications-alerts.md).
3. **System Feature Flags** — platform-wide, not scoped to any business at
   all (`system_feature_flags` table, keyed by `key`). Answers "has Super
   Admin turned this integration off everywhere?" No middleware — checked
   inline via `SystemFeatureFlagService::isEnabled($key)` at the specific
   call sites that opted in (e.g. `FirebaseNotificationService::sendToToken()`).

A route can legitimately carry both `module:` and `platform:` middleware at
once (see `routes/offline.php`) — they answer different questions and neither
implies the other.
