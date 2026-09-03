# Loyalty Program

A points-based reward system on top of Sales & POS: customers earn points on
qualifying paid/completed orders and redeem them as a discount on a later
order. Mirrors the existing store-credit feature's aggregate+ledger pattern
(`CustomerStoreCreditService` / `customer_store_credit_transactions`) almost
exactly — read that first if you already know it.

## Schema

| Table / Column | Purpose |
|---|---|
| `customer_settings.loyalty_program` | Business-wide on/off switch, default `false` |
| `customer_settings.loyalty_earning_mode` | `enum('order','product')`, default `order` |
| `customer_settings.loyalty_every_amount` / `loyalty_point_rate` | The earn formula: `floor(eligible_amount / every_amount) * point_rate` |
| `customer_settings.loyalty_min_order_amount` | Order must be at least this to earn anything |
| `customer_settings.loyalty_redemption_value` | Currency value of 1 point when redeemed, default `1.00` |
| `products.is_loyalty_enabled` / `product_variations.is_loyalty_enabled` | Per-item opt-in, only consulted when `loyalty_earning_mode = 'product'` |
| `customer_profiles.loyalty_points` | Available (spendable) balance |
| `customer_profiles.loyalty_points_reserved` | Reserved balance — locked against an unpaid order, not yet spendable, not yet permanently consumed |
| `customer_loyalty_transactions` | Append-only ledger, one row per balance-changing event. `transaction_type` enum: `earned`, `reserved`, `released`, `consumed`, `reversed`, `adjusted`, `expired` (`expired` is reserved for a future points-expiry feature — unused today). Carries `points`, `monetary_value`, `available_balance_after`, `reserved_balance_after`, and `reference_type`/`reference_id` (`'order'`/`order_id` or `'order_return'`/`order_return_id`) |
| `orders.loyalty_points_used` / `loyalty_discount_amount` / `loyalty_points_earned` | Frozen at save()/post() time — mirrors `voucher_discount_amount`, so a later change to `CustomerSetting` never rewrites an already-placed order's history |
| `accounting_settings.default_loyalty_discount_account_id` | COA account the redemption discount posts to (see Accounting below) |

Migrations: `2026_09_03_150100`–`2026_09_03_150800` (`database/migrations/`).

## `LoyaltyPointService` (`app/Services/Concrete/Admin/LoyaltyPointService.php`)

Single source of truth for every loyalty rule. Every mutating method locks
the `CustomerProfile` row first (`lockForUpdate`) and assumes the caller is
already inside its own DB transaction — this class never opens/commits one
itself, so it can be atomic with the caller's own posting (`OrderService`,
`OrderReturnService`).

Read-only / calculation:
- `isEnabled($businessId)`, `getSetting($businessId)` — program on/off and raw settings row.
- `getBalances($businessId, $customerId)` — `['available' => ..., 'reserved' => ...]`.
- `productEligible($businessId, Product $product, ?ProductVariation $variation)` — always `true` in `'order'` mode; in `'product'` mode, `true` only if the product/variation's `is_loyalty_enabled` flag is set. Single source of truth for earning eligibility (and intended for a future storefront "eligible" badge — not yet wired to any product listing endpoint).
- `calculateEarning(Order $order)` — pure calculation of what a paid/completed order would earn (0 if disabled, no customer, below minimum, or no eligible lines in product mode). Does not touch any balance.
- `calculateRedemption($businessId, $customerId, float $cap)` — how many available points (and their value) can be redeemed against `$cap` (the order's payable total before loyalty), capped at both the customer's available balance and `$cap`. Returns `['points' => ..., 'value' => ...]`.

Balance mutations (each records one `customer_loyalty_transactions` row):
- `earn(Order $order)` — credits available balance for a qualifying order. Called once from `OrderService::applyPostedEffects()`.
- `reserve(...)` / `release(...)` — move points available ↔ reserved. `reserve()` throws if the customer doesn't have enough available.
- `consume(...)` — permanently spends reserved points (order paid). Throws if more is consumed than is reserved.
- `reverse(...)` — restores previously-consumed points to available (a paid order was later voided).
- `revokeEarned(...)` — takes back previously-earned available points (order voided or returned). Throws if the customer has already spent enough of their balance that the take-back can't be absorbed — surfaces the mismatch instead of going negative.
- `adjust($businessId, $customerId, float $delta, ?$description)` — manual admin correction, guarded against a negative resulting balance. **No controller calls this yet** (see Permissions gap below).

Order-lifecycle helpers:
- `syncReservation($businessId, $customerId, $orderId, $orderReference, $desiredPoints, $description)` — idempotent re-reservation for a draft/held order whose loyalty selection may change across repeated `save()` calls: releases whatever is currently net-reserved for the order, then reserves the new amount (0 is valid — releases everything, reserves nothing).
- `releaseReservedForOrder($businessId, $customerId, $orderId, $description)` — releases whatever's net-reserved for an order back to available (draft/held order cancelled before ever posting).
- `reservedForOrder()` / `consumedForOrder()` / `earnedForOrder()` — net points still outstanding for an order, computed from the ledger (positive-type sum minus negative/offsetting-type sum), so callers never have to separately track state.
- `sumPointsForReference($referenceType, $referenceId, $transactionType)` — raw ledger sum, used by `OrderReturnService` to find how much was previously revoked for a specific return.

## Integration: `OrderService`

- **`saveLinesAndComputeTotals()`** — when the request carries `use_loyalty_points` truthy and the order has a customer, computes redemption last, on top of every other discount (line discounts, order discount, voucher), capped at the order's payable total before loyalty. Returns `loyalty_points_used` / `loyalty_discount_amount` alongside the other totals.
- **`save()`** (draft/hold) — persists those two fields, then calls `LoyaltyPointService::syncReservation()` so the reservation always matches the current cart, even across repeated saves of the same held order.
- **`cancel()`** (draft/hold only) — calls `releaseReservedForOrder()` inside its own transaction before flipping status, so a cancelled order's reservation is always released.
- **`applyPostedEffects()`** (called by `post()` and by `correct()`'s repost) — `consume()`s the reserved points (order now paid), then `earn()`s new points and stamps the result onto `$order->loyalty_points_earned`. Earning only ever happens here — never on a draft/held order.
- **`reversePostedEffects()`** (shared by `void()` and by `correct()`'s pre-rebuild step) — `reverse()`s whatever was consumed (`consumedForOrder()`) and `revokeEarned()`s whatever was earned (`earnedForOrder()`) for the order.
- **`correct()`** — reverses via `reversePostedEffects()`, rebuilds the cart (`rebuildPostedOrderCart()`), re-syncs the reservation for the corrected `loyalty_points_used`, then re-applies via `applyPostedEffects()` — same two-phase pattern used for stock/accounting.
- Posting guard: `post()`/`applyPostedEffects()` requires `accounting_settings.default_loyalty_discount_account_id` to be configured whenever `order->loyalty_discount_amount > 0`, throwing otherwise (same pattern as the Discount account check).

## Integration: `OrderReturnService`

- **`applyOrderReturnPosting()`** (return approved) — after posting the return's stock/accounting reversal, revokes a **proportional** share of the order's still-net-earned points: `share = min(1, return_total / order_total)`, `points_to_revoke = round(remaining_earned * share, 3)`, via `revokeEarned()` with `reference_type = 'order_return'`. Capped by whatever is still net-earned, so repeated/partial returns on the same order never over-revoke.
- **`reverseOrderReturnPosting()`** (return un-approved: rejected after approval, or deleted while approved) — looks up how much was revoked for this return (`sumPointsForReference('order_return', ..., 'adjusted')`) and `reverse()`s it back to available.

## Accounting

A **Loyalty Points Discount** account (code `530003-001`, under
Administrative Expenses, parent `530003`) is seeded by
`ChartOfAccountsTemplateSeeder` and cloned into every business — new
businesses via `AccountingSetupWizardService::setupForBusiness()` →
`ChartOfAccountsCloneService` + `AccountingSettingCloneService`; existing
businesses backfilled one-time by migration
`2026_09_03_150800_backfill_loyalty_coa_for_existing_businesses` (re-runs
both clone services per business, idempotent, additive-only — never touches
an account/setting a business already has). The mapping lives on
`accounting_settings.default_loyalty_discount_account_id`, remappable under
**Settings → Accounting** (`SettingController::updateAccountingSetting`,
`resources/views/admin/setting/tabs/accounting.blade.php` — "Loyalty
Discount Account" dropdown).

`OrderService::post()`'s Sale Voucher journal entry posts a debit leg to this
account (separate from the general Discount account) whenever
`order->loyalty_discount_amount > 0`, alongside the existing
Discount/Tax/Sale legs.

## Permissions

Registered under the `loyalty` module key in `PermissionRegistry`
(`app/Support/Permissions/PermissionRegistry.php`), plus related entries
under `order` and `reports`:

| Permission | Label | Enforcement status |
|---|---|---|
| `loyalty.view` | View | Not yet consumed by any controller |
| `loyalty.edit` | Manage Settings | Not yet consumed — the Loyalty Program fields under Settings → Customer are actually gated by `setting.manage` (`SettingController`'s constructor middleware), same as every other setting tab |
| `loyalty.adjust` | Manual Point Adjustment | Not yet consumed — `LoyaltyPointService::adjust()` has no controller/route calling it yet |
| `loyalty.history` | View Customer Loyalty History | Not yet consumed |
| `order.loyalty.apply` | Apply Loyalty Points (under `order`) | Registered and included in the Cashier/Sales Manager role defaults, following the same context-dependent-permission convention as `order.discount.apply`/`order.coupon.apply` (see [Permissions & Access Control System](05-permissions-access-control.md)), but `OrderController` does not yet strip/check `use_loyalty_points` the way it does `discount_id`/`voucher_code` — unlike those two, it is not currently enforced |
| `reports.loyalty-report.*` (`view`, `print`, `pdf`, `export`, `export-csv`) | Loyalty Report | Reachable — `LoyaltyReportController` (`app/Http/Controllers/Admin/Reports/Orders/LoyaltyReportController.php`, extends `BaseOrderReportController`) is registered in the `routes/web.php` `$orderReportRoutes` loop (prefix `loyalty-report`) and its view (`admin.reports.loyalty_report.index`/`pdf`) exists; gated via `permissionName()` returning `reports.loyalty-report.view` |
| `reports.customer-loyalty-report.*` (`view`, `print`, `pdf`, `export`, `export-csv`) | Customer Loyalty History Report | Reachable — `LoyaltyHistoryReportController` (`app/Http/Controllers/Admin/Reports/LoyaltyHistoryReportController.php`) is registered directly in `routes/web.php` (not via the generic report loop) and its view (`admin.reports.customer_loyalty_report.index`/`pdf`) exists; each action is gated by its own `permission:reports.customer-loyalty-report.*` constructor middleware |

`loyalty` is included in `PermissionRegistry::operationalModuleKeys()`, so it
flows automatically into broad manager-style role defaults. All six
`loyalty.*`/`order.loyalty.apply` permissions plus both report permission
groups are added to the Sale Manager default set in
`RoleDefaultPermissions::defaultsForRole()`. Re-run
`php artisan db:seed --class=PermissionSeeder` after any registry change, per
the usual convention.

## API Routes (Website & Mobile)

Loyalty rides on the existing checkout/profile contract plus a small
dedicated `Loyalty` surface, identical shape on both clients (see
[Platform Ecosystem](14-platform-ecosystem.md)):

- `POST /api/v1/checkout/{business_id}` (`App\Http\Controllers\Api\CheckoutController::placeOrder`, service `WebsiteCheckoutService`) and its mobile twin `POST /api/mobile/checkout/{business_id}` (`Api\Mobile\CheckoutController`) both accept an optional `use_loyalty_points` boolean; when truthy it's forwarded to `OrderService::save()` as `order_data['use_loyalty_points']`.
- `GET /api/v1/profile/{business_id}` / `GET /api/mobile/profile/{business_id}` (`ProfileController::show`, service `CustomerAccountService::getProfilePayload()` — the mobile controller's `MobileCustomerAccountService extends CustomerAccountService` with no overrides, so it shares the same implementation) return a `loyalty` block: `{ enabled, available, reserved }`. Also surfaced on login (`Api\Auth\AuthController`, `Api\Mobile\Auth\AuthController`).
- `GET /api/v1/loyalty/{business_id}` / `GET /api/mobile/loyalty/{business_id}` (`Api\LoyaltyController::show` / `Api\Mobile\LoyaltyController::show`, service `CustomerLoyaltyService` / `MobileLoyaltyService extends CustomerLoyaltyService`) — balance summary for the authenticated customer: `{ enabled, available, reserved, redemptionValue }`. When the program is off for the business, still returns `200` with `enabled: false` and every other field `null` (never a `404`), so the frontend gets a clean "hide the UI" signal.
- `GET /api/v1/loyalty/{business_id}/history` / `GET /api/mobile/loyalty/{business_id}/history` (`LoyaltyController::history`, `CustomerLoyaltyService::history()`) — the authenticated customer's own `customer_loyalty_transactions` rows, newest first, paginated identically to `CustomerOrderController::index` (`page`/`per_page`, capped at 50/page; response shape `{ data, current_page, per_page, total, last_page }`). Each row: `{ id, transactionType, points, monetaryValue, availableBalanceAfter, reservedBalanceAfter, referenceType, referenceId, description, dateCreated }`.
- `GET /api/v1/products/{business_id}` / `GET /api/mobile/products/{business_id}` (`ProductService::getWebsiteListing()`, reused as-is by `MobileCatalogService::products()`) and the single-product detail equivalents now include a `loyaltyEligible` boolean per product summary (and per variation `option` on the detail endpoint), computed via the identical rule as `LoyaltyPointService::productEligible()` — used by the storefront/app "earns points" coin badge. To avoid an N+1 (`productEligible()` re-queries `CustomerSetting` internally), `ProductService::loyaltyEligibilityContext($business_id)` resolves the program's enabled/mode-product state **once per request** and `resolveLoyaltyEligible()` applies it per product/variation from the already-loaded `is_loyalty_enabled` column — no extra query per row.

## Admin UI

- **POS screen** (`resources/views/admin/pos/screen/index.blade.php`,
  `PosScreenController@index`, `public/assets/js/admin/pos-screen.js`) — a
  "Use Loyalty Points" checkbox sits alongside the Discount/Voucher fields in
  the checkout panel, rendered only when `$customer_setting->loyalty_program`
  is on (`CustomerSetting::where('business_id', ...)`, fetched the same way
  `PosScreenController` already fetches `$pos_setting`). A small hint next to
  it shows the selected customer's available points ("120 pts available (~Rs
  120)"), read from a `data-loyalty-points` attribute baked onto each
  customer `<option>` at page load — the same pattern `data-store-credit-
  balance` already uses for Store Credit, not a dedicated AJAX balance
  lookup. Checking the box sends `use_loyalty_points: true` in
  `buildStorePayload()`'s payload alongside `voucher_code`/`voucher_id`
  (`OrderController::store()`/`correct()`/`previewVoucher()` strip it back
  out server-side when the user lacks `order.loyalty.apply`, mirroring the
  existing `order.discount.apply`/`order.coupon.apply` stripping — there is
  no client-side `@can`/`can()` gate either, matching how those two sibling
  fields are already (not) gated in this Blade file). The cart totals
  sidebar gets its own "Loyalty Discount" row (hidden until
  `loyalty_discount_amount > 0`), populated from the `save()`/`hold()`
  response's `loyalty_discount_amount` and broken back out of the combined
  `discount_amount` the same way `Item Discounts` already is; the live
  voucher/discount preview (`OrderController::previewVoucher()`) still folds
  loyalty into the combined "Order Discount" figure in real time, since
  `previewVoucher()`'s return shape wasn't changed to break it out
  separately. Resuming a held/draft order with `loyalty_points_used > 0`
  re-checks the box, mirroring `header.voucher_code` restoration — since
  `OrderService::getDetails()`'s `header` array doesn't carry the loyalty
  columns, `OrderController::details()` merges them in from the `Order` row
  itself before returning.
- **Order Details** (`resources/views/admin/order/show.blade.php`) — the
  Order Information card shows a "Loyalty Points" block (redeemed points +
  `loyalty_discount_amount`, and points earned) next to the existing Voucher
  block, whenever `loyalty_points_used` or `loyalty_points_earned` is
  non-zero. The Line Items totals table adds a "Loyalty Discount" row
  (mirroring the "Voucher Discount" row) before Tax, whenever
  `loyalty_discount_amount > 0`. All values are read directly off the frozen
  `Order` columns — never recomputed from current `CustomerSetting` values.
- **POS thermal receipt** (`resources/views/admin/order/print/thermal.blade.php`)
  — a "Loyalty" totals row (points redeemed + `loyalty_discount_amount`,
  mirroring the existing "Voucher" row) and a "Points Earned" row in the
  payment block, both gated by a new `loyalty` field key on
  `ThermalPrintConfig::isVisible()`. Since `isVisible()` fails open for any
  field key not present in a business's saved `field_config` (see the
  class's docblock), this key needs no seeding/migration to show by default;
  a business can later hide it once the field-toggle admin screen adds a
  matching checkbox (not yet done — out of scope for this pass). The A4 POS
  print view (`admin/order/print/print.blade.php`) has no equivalent Voucher
  Discount row either, so it was left untouched for consistency.
- **Customer Show page** (`resources/views/admin/customer/show.blade.php`,
  `CustomerController@show`) — two summary cards ("Loyalty Points Available"
  / "Loyalty Points Reserved", reading `CustomerProfile::loyalty_points` /
  `loyalty_points_reserved` directly) next to the existing Store Credit
  Available card, plus a "Loyalty History" tab listing
  `CustomerLoyaltyTransaction` rows (type, points, monetary value, date,
  `available_balance_after`, and a link to the order for `reference_type =
  'order'` rows). All of this is wrapped in
  `$customer_setting->loyalty_program ?? false` and hidden entirely when the
  business has the program off. This is a **standalone** table, not merged
  into the existing Order & Payment History / Transaction Timeline tabs —
  `CustomerService::getCustomerHistory()`/`getCustomerTimeline()` only ever
  merge `Order` + `CustomerPayment` rows (store credit is also not merged
  there; `store_credit_balance` is read as a plain column, not a ledger
  timeline), so there was no existing generic-timeline pattern to extend.
  The controller fetches `$customer_setting` and `$loyalty_transactions`
  (latest 50, newest first) the same way it already fetches `$history`/
  `$timeline`.
- **Product create/edit screen** (`resources/views/admin/product/create.blade.php`
  — `edit()` reuses the same view/route as `create()`) — a product-level
  "Loyalty Enabled" checkbox next to `is_featured`/`is_trending`/
  `is_best_seller`, and a per-variation "Loyalty Enabled" checkbox in the
  variation modal, built into the `variations[]` JSON payload alongside
  `discount_apply_all`. Both are visible only when
  `customer_setting->loyalty_program` is on **and**
  `loyalty_earning_mode == 'product'` — the product-level checkbox via a
  server-rendered `@if`, the variation-modal checkbox via a
  `window.loyaltyProductModeEnabled` JS flag (server-rendered from the same
  condition) that the inline variation-row builder script checks before
  showing/reading it. `ProductController@create`/`@edit` fetch
  `$customer_setting` (business-scoped: the logged-in business admin's
  `business_id` for `create()`, `$product->business_id` for `edit()` — a
  Super Admin creating a product before picking a business sees no loyalty
  checkbox until they save and reopen it in edit mode, since the business
  picker is AJAX-driven and doesn't reload this setting). Validation,
  persistence (`ProductController@store`, `ProductService`) were already in
  place before this UI was added.

## Not Yet Built (documented gap, not silently dropped)

- No admin field-toggle checkbox yet for the thermal receipt's new `loyalty`
  field key (see Admin UI above) — it fails open (always visible) until one
  is added.

See also: [Modules, Controllers & Services](03-modules-controllers-services.md),
[Permissions & Access Control System](05-permissions-access-control.md),
[Database Schema & Relationships](02-database-schema.md).
