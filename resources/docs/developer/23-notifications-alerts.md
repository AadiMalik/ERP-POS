# Notifications, Alerts & Access Control

Covers the in-app notification system end-to-end (ERP, Website/Mobile customer,
POS) and the two Super-Admin-only access-control layers introduced alongside
it: Business Access Control and System Feature Flags. See
[Subscription & Module Gating](08-subscription-module-gating.md) → "Three
Separate Gating Systems" for how these relate to the pre-existing package
gating.

## Notification Core (schema + dispatch)

`notifications` (event/content: `business_id`, `branch_id`, `type`, `title`,
`message`, `reference_type`/`reference_id`, `url`, `data` json, `dedupe_key`,
unique on `(type, reference_type, reference_id, dedupe_key)` as the
duplicate-send guard) + `notification_recipients` (per-`user_id` read state -
`is_read`, `read_at`). One `Notification` row, many `NotificationRecipient`
rows.

`App\Services\Concrete\Admin\NotificationDispatchService::dispatch()`:
```php
dispatch(
    string $type, ?string $businessId, ?string $branchId,
    string $title, string $message,
    ?string $referenceType, ?string $referenceId, ?string $url, ?array $data,
    string $dedupeKey,
    ?array $roles = null,          // null = defaultRoles($type); [] = no role-based recipients
    ?array $explicitUserIds = null // customer/POS user ids, merged in alongside any role recipients
): ?Notification
```
`resolveRecipients()` resolves `$roles` (`SUPERADMIN`/`BUSINESSADMIN`/`BRANCHADMIN`)
against `business_id`/`branch_id`. `resolvePosRecipients(businessId, branchId)`
is the separate helper for "the relevant POS": every active `PosRegister`'s
`assigned_user_id` in that branch, falling back to branch-scoped users holding
`pos.access` if no register has an assigned cashier (there is no
"default register per branch" concept to use instead).

`App\Traits\Notifiable::notify(...)` is the Service-layer convenience wrapper
(same explicit-call convention as `Auditable::logActivity()` - not an Eloquent
observer), defaulting `businessId`/`branchId` to `Auth::user()` and adding the
same trailing `$roles`/`$explicitUserIds` passthrough.

All parameters past `$dedupeKey` are optional and additive, so every
pre-existing call site (12+, all periodic/threshold alerts) is unaffected by
this extension.

## Every Notification Type

| Type | Fired from | Recipients | Gate |
|---|---|---|---|
| `low_stock` / `out_of_stock` | `CheckNotificationAlertsCommand::checkLowStock()` | Business/Branch Admin | `InventorySetting.low_stock_alert` |
| `new_order` | `OrderService::recordStatusHistory()` (creation branch) | Business/Branch Admin | `NotificationSetting.new_order_alert_enabled` |
| `order_placed` | same, when order source is WEBSITE/MOBILE_APP and the buyer has a `CustomerProfile` | the customer (`explicitUserIds: [$order->user_id]`, `roles: []`) | always (no toggle - it's the customer's own order) |
| `order_placed_pos` | same, immediately after `order_placed` | `resolvePosRecipients()` for the order's branch | `NotificationSetting.website_order_notify_pos_enabled` (default true) |
| `order_status` | `recordStatusHistory()` (transition branch) | Business/Branch Admin | `NotificationSetting.order_status_alert_enabled` |
| `order_status_changed` | same, customer orders only | the customer | always |
| `payment_due` | `CheckNotificationAlertsCommand::checkPaymentDue()` | Business/Branch Admin | `NotificationSetting.payment_due_alert_enabled` |
| `customer_credit_due` | same call site, second dispatch | the customer | always, alongside `payment_due` |
| `credit_limit` | `checkCreditLimit()` - a *different* alert ("outstanding ≥ credit limit"), not "Customer Credit Due" | Business/Branch Admin | `NotificationSetting.credit_limit_alert_enabled` |
| `supplier_payment_reminder` | `checkSupplierPaymentReminder()` | Business/Branch Admin | `NotificationSetting.supplier_payment_reminder_enabled` |
| `subscription_expiry` | `checkSubscriptionExpiry()` | **Super Admin only** (`roles: [RoleNames::SUPERADMIN]`) | `SubscriptionSetting.expiry_alert_days_before` |
| `backup_failed` | `BackupService` | **Super Admin only** | auto-backup enabled + a run fails |

`checkPaymentDue()` is the "Customer Credit Due" alert per these requirements
(credit-days-based due date) - don't confuse it with `checkCreditLimit()`,
an older, separate alert.

### Order Notifications Are a Single Hook Point

`OrderService::recordStatusHistory($order_id, $from_status, $to_status, $reason)`
is called for both order **creation** (`$from_status === null`, from `save()`)
and every later status **transition** (`changeStatus()`, `cancel()`, `post()`,
`void()`, hold-resume). Rather than adding a second "new order" hook
elsewhere in `save()` (which would double-notify, since this method already
fired a business-facing alert on creation before this feature), the method's
notification block branches once on `$from_status === null` to cover
new-order/order-placed/order-to-POS, and once on the `else` to cover
status-change/status-changed-for-customer. `$is_customer_order` is resolved
once per call via the order's `OrderSource.code` (`WEBSITE`/`MOBILE_APP`) and
a `CustomerProfile` existence check (so a walk-in `user_id` is never
mistaken for a real customer). This also fixed a latent bug: the
`order_status` dispatch previously always passed `business_id`/`branch_id` as
`null, null`, relying entirely on `Auth::user()` inside `Notifiable` - it now
uses the order's own `business_id`/`branch_id`, which matters for
non-web-request contexts.

## Business-Level Settings

`notification_settings` (one row per business) gained two columns alongside
the pre-existing ones: `new_order_alert_enabled` and
`website_order_notify_pos_enabled` (both boolean, default `true`). Both are
on the Notification Settings tab (`SettingController::updateNotificationSetting()`).

## Customer-Facing Delivery (Website/Mobile)

`App\Http\Controllers\Api\Mobile\NotificationController` and
`App\Http\Controllers\Api\NotificationController` are thin, near-identical
controllers (mirroring the existing Website/Mobile controller-pair convention,
e.g. `CheckoutController`/`CustomerOrderController`) exposing `index`,
`unread-count`, `{id}/read`, `mark-all-read` under `routes/mobile.php` /
`routes/api.php`. They reuse `App\Services\Concrete\Admin\NotificationService`
completely unchanged - its queries already scope by `NotificationRecipient.user_id`,
which already isolates per-user regardless of business, so no service change
was needed for the customer surface. Rendering the bell/toast/sound on the
actual Website/Mobile App is the responsibility of those (external, not in
this repo) frontends.

## POS-Facing Delivery

`NotificationService::unreadCount()`/`latest()` both gained an optional
trailing `?string $type = null` filter (backward compatible - the ERP navbar's
existing calls are unaffected). `PosScreenController::posNotificationsUnreadCount()`/
`posNotificationsLatest()` call these with `'order_placed_pos'` hard-coded,
routed inside the existing `permission:pos.access` group. The POS header
(`resources/views/layouts/pos-header.blade.php`) polls these two endpoints
every 30s with the same WebAudio-beep-on-increase pattern as the ERP navbar
bell (`resources/views/layouts/navbar.blade.php`), gated by the same
`NotificationSetting.sound_enabled`.

The "never send other general notifications to POS/Offline POS" requirement
holds structurally, not by an extra filter: `order_placed_pos` is the *only*
type ever dispatched via `resolvePosRecipients()`, and the POS bell only ever
polls that one type - there is no code path that could put a `low_stock` or
`new_order` notification in front of a POS cashier through this surface.

## Business Access Control

Four boolean columns on `businesses` (`erp_access_enabled`,
`storefront_access_enabled`, `pos_access_enabled`, `offline_pos_access_enabled`,
all default `true`). Website and Mobile App share `storefront_access_enabled`
because they share the exact same `/api/mobile`+`/api/v1` route surface today
with no client-identifying signal - see
[Routes & APIs](04-routes-apis.md#mobile-app-customer-api-routesmobilephp).

**Enforcement:**
- ERP login: `LoginController::attemptLogin()` (overridden the same way
  `sendFailedLoginResponse()` already was - `AuthenticatesUsers` is a trait,
  not a parent class) checks `erp_access_enabled` before calling
  `guard()->attempt()`, so a blocked business never gets a session regardless
  of password correctness.
- ERP mid-session: `CheckBusinessSubscription` (already the middleware that
  force-logs-out suspended/no-subscription businesses on every `admin/*`
  request) gained one more check for `erp_access_enabled`, right after its
  existing "business not found" guard.
- Everything else: `App\Http\Middleware\EnsurePlatformAccess`
  (`platform:<storefront|pos|offline-pos>` route middleware, mirrors
  `EnsureModuleEnabled`'s structure exactly - real HTTP middleware so it's
  safe outside a request context, e.g. `route:list`; Super Admin always
  bypasses). Resolves the target business from `$request->route('business_id')`
  if the route has one, else `Auth::user()->business_id`.

`BusinessAccessControlController` (`admin/business-access-control`, `superadmin`
+ `permission:business-access-control.*`) is the toggle UI -
`BusinessService::togglePlatformAccess()`/`getAccessControlData()`.

## System Feature Flags

`system_feature_flags` (uuid PK, unique `key`, `label`, `description`,
`category`, `is_enabled` default `true`) - a standalone, platform-wide
registry, not scoped to any business and not part of the Package/module-tier
system. `SystemFeatureFlagService::isEnabled($key)` fails open (`true`) for an
unregistered key, so it can never accidentally restrict behavior that hasn't
explicitly opted in. Seeded (idempotent upsert by `key`, via
`SystemFeatureFlagSeeder`) with two concrete example flags actually wired in:
- `push_notifications` → guards the top of
  `FirebaseNotificationService::sendToToken()`.
- `online_payment_gateways` → guards the top of
  `PaymentService::listAvailableGateways()`.

`SystemFeatureFlagController` (`admin/system-feature-flags`, same `superadmin`
+ `permission:system-feature-flag.*` pattern) is the toggle UI. Adding a new
flag: register a row (seeder or manually), then call
`app(SystemFeatureFlagService::class)->isEnabled('your_key')` at whichever
call site should respect it - no framework wiring beyond that.

## Permissions

Two new `is_system: true` modules in `PermissionRegistry`:
`business-access-control` (`view`, `manage`) and `system-feature-flag`
(`view`, `manage`). `RoleDefaultPermissions::SUPERADMIN` already returns
`PermissionRegistry::allNames()`, so Super Admin gets both automatically with
no separate change. Neither is in `operationalModuleKeys()`/`businessNames()`
(same as `business`/`backup`).
