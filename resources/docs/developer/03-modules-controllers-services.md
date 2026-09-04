# Modules, Controllers & Services

Every admin route lives under one route group in `routes/web.php`:
```php
Route::group(['middleware' => ['auth', 'check.subscription', 'setting', 'must-change-password'], 'prefix' => 'admin'], function () { ... });
```
Sub-groups layer `module:<key>` (subscription-tier gate), `permission:<key>`
(RBAC gate), or `superadmin` on top. This page is the map from **business domain →
controllers → route prefixes**; pair it with
[Database Schema & Relationships](02-database-schema.md) (the tables) and
[Routes & APIs](04-routes-apis.md) (the gating middleware table).

## Roles & Permissions (Core, always on)
`PermissionController`, `RoleController` — `admin/permissions`, `admin/roles`.

## Packages & Business/Tenant Management (Core, platform admin)
`PackageController`, `BusinessController`, `BranchController` — `admin/packages`,
`admin/business`, `admin/branch`.

## Subscriptions & Billing (SaaS)
`SubscriptionController`, `SubscriptionRenewalRequestController`,
`SubscriptionInvoiceController`, `SubscriptionPaymentController`,
`SubscriptionSettingController` (Super Admin, `superadmin` middleware) +
`MySubscriptionController` (business self-service, ungated) — prefixes
`admin/subscriptions`, `admin/subscription-renewal-requests`,
`admin/subscription-invoices`, `admin/subscription-payments`,
`admin/subscription-settings`, `admin/my-subscription`.

Business Admin **My Subscription** shows a Monthly/Yearly price table of active
packages (`FeatureLimitService::compareToPackage` gates downgrade/upgrade
requests). Catalog plans are seeded by `IntroPackageCatalogSeeder` (Starter /
Growth / Business / Enterprise × monthly + yearly, with `discount` % on the
package). Super Admin creates and edits packages via `PackageController`
(price + discount + duration_type + module matrix).

## Users, Customers & Profile (Core CRM/Identity)
`UserController`, `CustomerController`, `ProfileController`, `SearchController`
(global header search — each result group gated by its own module's view
permission) — `admin/users`, `admin/customer`, `admin/profile`, `admin/search`.

Web login / forgot-password lives outside the admin group:
`App\Http\Controllers\Auth\LoginController`,
`ForgotPasswordController` (OTP via `OtpService`, not Laravel's password-reset
broker). Guest screens use `resources/views/layouts/auth.blade.php`. Password
inputs share `resources/views/partials/password-input.blade.php`.
Forgot-password first looks up an active, non-deleted `users` row for the email;
if none exists it stays on the form with “This email is not registered.” and
does not send mail. A registered email gets a Dukanaz-branded OTP
(`emails.otp` / `emails.otp-text`). All website API OTPs
(`send-otp` onboarding/login and `forgot-password`) use
`OtpService::send(..., 'storefront')` — that business’s logo/name/colors with
a Powered by Dukanaz footer (`emails.otp-storefront`). Forgot-password also
checks `CustomerAccountService::emailExistsForBusiness()` for the given
`business_id` before sending. The mobile app mirrors the same OTP/auth flow
under `/api/mobile/auth/*` via `App\Http\Controllers\Api\Mobile\Auth\AuthController`
and `MobileCustomerAccountService` (see [Routes & APIs](04-routes-apis.md)).

## Mobile App Customer API
Controllers: `App\Http\Controllers\Api\Mobile\*` (Auth, catalog, cart, checkout,
wishlist, profile, orders, CMS). Services: `App\Services\Concrete\Api\Mobile\*`
(`MobileCartService`, `MobileCheckoutService`, `MobileWishlistService`,
`MobileOrderService`, `MobileCustomerAccountService` extend the website Api
services; `MobileCatalogService`, `MobileCmsService`, `MobileStoreConfigService`
wrap Admin public helpers). Routes: `routes/mobile.php` → `/api/mobile/...`.

## Inventory (`module:inventory`)
`WarehouseController`, `BrandController`, `CategoryController`,
`SubCategoryController`, `UnitController`, `ProductController`,
`BarcodeController`, `ProductVariationUnitConversionController`,
`ProductVariationBatchController`, `ProductVariationStockController`,
`ProductVariationStockTransactionController`.

**Inventory Reporting System** (sidebar under Inventory → Reports with Stock /
Consumption / Manufacturing / Recipe-BOM groups): controllers in
`App\Http\Controllers\Admin\Reports\Inventory\` plus existing
`StockLedgerReportController` and manufacturing report controllers. See
[Reports Infrastructure](06-reports-infrastructure.md#inventory-reporting-system)
and [Manufacturing](16-manufacturing.md#reports).

Storefront homepage product rails are built by
`ProductService::buildWebsiteSections()` (attached to
`GET /api/v1/products/{business_id}` on unfiltered page 1, and reused by
`WebsiteHomeService`). Each rail is capped at 12:

| Section | Primary source | Fallback fillers |
|---|---|---|
| Featured | `is_featured = 1` | Other website-visible products (newest first), excluding IDs already in the rail |
| Trending | `is_trending = 1` | Same filler rule |
| New Arrivals | Newest website-visible products | Same filler rule (usually a no-op) |
| Best Sellers | `is_best_seller = 1` | Same filler rule |
| Discounted | Variations with a valid current discount (`discount_percentage > 0` and `discount_apply_all` or matching sale type); resolved discount must stay &gt; 0 | **None** — empty array; storefront hides the whole Discounted section |

Fillers never duplicate a product inside the same section. Themes hide empty
product sections (`v-if` on length) so the homepage never shows a blank rail
or a “Products Not Found” empty state for these groups.

### Batch & Expiry Tracking

Optional per `product_variations.track_batch` / `track_expiry` (opt-in per
variation; a product without either flag is untouched by any of this). The
business-level master switches are `inventory_settings.enable_batch_no` /
`enable_expiry_date`, plus `block_expired_sale` (default `true`),
`batch_selection_strategy` (`fefo`|`fifo`, default `fefo`), and
`near_expiry_days` (default `30`, used only by the Batches report filter).

**Model:** `ProductVariationBatch` (`product_variation_batches` table) is a
warehouse-scoped batch ledger — `batch_no`, `avg_price`, `quantity`,
`manufacturing_date`, `expiry_date` — one row per
business+warehouse+product+variation+batch_no, alongside the pre-existing
aggregate `ProductVariationStock` row (unchanged, still one row per
business+warehouse+product+variation). CRUD: `ProductVariationBatchController`
/ `ProductVariationBatchService`.

**Receiving → batch (find-or-create-and-roll-forward pattern):**
`ProductVariationStockService::upsertReceiptBatch()` is the one shared
implementation of the pattern originally established by
`OpeningStockService::applyOpeningStockPosting()` (still the reference
implementation for how a single receiving line resolves to a batch) — it
no-ops (returns `null`) unless the variation tracks batch/expiry and a
`batch_no` was supplied, otherwise finds-or-creates the
`ProductVariationBatch` row and rolls its `quantity`/`avg_price` forward the
same way the aggregate stock row is. Called from
`PurchaseService::applyDirectPurchaseApproval()` (batch fields live on
`purchase_details`: `batch_no`, `manufacturing_date`, `expiry_date`,
`product_variation_batch_id`) and `GrnService::applyGrnApproval()` (same
fields on `good_receipt_note_details`) — a direct Purchase posts stock (and
so captures batch info) immediately on approval, a `purchase_request`-type
Purchase only does so via its GRN, so batch info is captured wherever the
actual receipt happens.

**Reversal:** `ProductVariationStockService::reverseStockTransactions()` is
the one shared implementation every `reverse*` method
(`PurchaseService::reverseDirectPurchaseApproval()`,
`GrnService::reverseGrnApproval()`,
`PurchaseReturnService::reversePurchaseReturnPosting()`,
`OrderService::void()`, `OrderReturnService::reverseOrderReturnPosting()`)
now calls instead of hand-rolling the soft-delete-transactions +
`recomputeLedger()` loop inline: it soft-deletes the given transactions,
reverses each one's batch delta (sign picked from
`TransactionType::isInbound()` — inbound reversed = decrement the batch,
outbound reversed = increment it) via `adjustBatchQuantity()`, then calls
`recomputeLedger()` per affected business/warehouse/product/variation exactly
as before. `recomputeLedger()`/`getLedger()` themselves are unchanged —
batch quantities are maintained directly by the apply/reverse paths, not
replayed from the ledger.

**Purchase Return → batch:** `PurchaseReturnService::applyPurchaseReturnPosting()`
resolves the batch to decrement via the return line's linked
`purchase_detail_id`/`good_receipt_note_detail_id` → `product_variation_batch_id`
(a return never picks its own batch, it reverses whichever one the original
receipt used).

**POS Sale → FEFO/FIFO draw-down:** `ProductVariationStockService::pickBatchesForSale()`
locks and returns the ordered list of batches (with quantity to draw from
each) needed to cover a line's `base_quantity`, honoring
`batch_selection_strategy` and `block_expired_sale`; returns `null` (never a
partial list) if the tracked batches on hand can't cover it, which
`OrderService::post()` treats as "insufficient stock" the same as an
untracked product (unless `allowsNegativeStock()` is on, in which case it
falls back to a plain aggregate decrement with no batch attributed — a
`ponytail:`-flagged simplification for a rare edge case). A line drawn from
a single batch stamps `order_details.product_variation_batch_id` and the
matching `ProductVariationStockTransaction` directly; a line split across
multiple batches instead writes one `order_detail_batches` row per batch
consumed (`order_detail_id`, `product_variation_batch_id`, `quantity`,
`base_quantity`) and one stock transaction per batch, each carrying its own
`product_variation_batch_id` — `order_details.product_variation_batch_id`
stays `null` in that case.

**Sale Return → batch:** `OrderReturnService::applyOrderReturnPosting()`
restores into `orderDetail.product_variation_batch_id` directly for a
single-batch line, or proportionally across `orderDetail.orderDetailBatches`
(by each row's share of the original line's `base_quantity`, last row
absorbing the rounding remainder) for a split line — one stock transaction
per batch restored.

**Reports:** `ProductVariationBatchService::getData()` (the Batches screen)
doubles as the batch-stock report — it takes an `expiry_status` filter
(`active`/`near_expiry`/`expired`, computed from `expiry_date` vs.
`near_expiry_days`) and renders a matching status badge column. The Stock
Ledger report (`StockLedgerReportService` /
`StockConsumptionViewController`) needed no changes — it already
eager-loads and displays `productVariationBatch` whenever a transaction
carries a `product_variation_batch_id`, which every path above now
populates.

**Not covered (documented gap, not silently dropped):** Transfer Note and
Stock Taking remain aggregate-only — see the Business docs
(`resources/docs/business/05-inventory.md`).

## Purchasing / Procurement (`module:inventory`)
`SupplierController`, `PurchaseRequestController`,
`PurchaseRequestQuotationController`, `PurchaseController` (service:
`PurchaseService`), `GoodReceiptNoteController` (service: `GrnService`),
`PurchaseReturnController` (service: `PurchaseReturnService`),
`OpeningStockController`, `StockTakingController`, `TransferNoteController`,
`SupplierPaymentController`.

### Transfer Notes (`TransferNoteController` / `TransferNoteService`)
Inter-branch/inter-warehouse stock transfers follow a controlled 4-state workflow,
mirroring the Purchase → GRN partial-receiving pattern rather than a one-shot move:

| Status | Meaning | Stock effect |
|---|---|---|
| `draft` | Header/lines editable, only the creator's changes | None |
| `in_transit` | Reached via **Send** (`TransferNoteService::send()`) | Deducts the source warehouse (`TRANSFER_OUT` ledger entry per line); destination untouched |
| `received` | Every line's `received_quantity` has caught up to `transfer_quantity` | N/A — set automatically once fully caught up |
| `cancelled` | Reached via **Cancel** (`destroy()` → `delete()`) | If cancelled while `in_transit`, reverses the `TRANSFER_OUT` entries via `ProductVariationStockService::recomputeLedger()`; blocked entirely once anything has been received |

**Receive** (`TransferNoteService::receive()`) adds to the destination warehouse
(`TRANSFER_IN` ledger entry per line, both entries sharing
`reference_type = STOCK_TRANSFER` / `reference_id = transfer_note_id`) and can be
called repeatedly for partial receiving — each call's `receive_quantity` per line is
capped at `transfer_quantity - received_quantity` on `transfer_note_details`, which is
what prevents both over-receiving and double-receiving an already-completed line.
Status flips to `received` only once every line is fully caught up
(`Status::RECEIVED`), otherwise it stays `in_transit` — exactly like a Purchase stays
`approved` while partially GRN'd (`PurchaseService::syncPurchaseCompletionStatus()`).

`transfer_notes.branch_id` is the source branch (denormalized from
`source_warehouse.branch_id`, as before); `destination_branch_id` is the new column
denormalized from `destination_warehouse.branch_id`. Authorization for Send/Cancel
checks the acting user against `branch_id`; Receive checks against
`destination_branch_id` — both via
`TransferNoteController::assertTransferNoteAccessible()`, which mirrors
`OrderController::assertOrderAccessible()` (an `applyRoleScope()` existence check,
`abort(403)` on failure). This is also why `applyRoleScope()`
(`app/Helpers/CommonFunctions.php`) gained an optional `$extra_branch_column`
parameter: without it, a branch-scoped role's list view was implicitly scoped only to
the source `branch_id`, hiding incoming transfers from destination-branch staff.
`TransferNoteService::getData()` now passes `'destination_branch_id'` as that extra
column so both sides of a transfer see it. The parameter defaults to `null` and every
other `applyRoleScope()` call site is unaffected.

Permissions: `transfer-note.send` and `transfer-note.receive` were added alongside
the existing `transfer-note.{view,create,edit,delete,print,import,export}` (the old
freeform `transfer-note.status` permission/route is retired from use but the
permission name itself is kept, per the permission-name-permanence rule). Cancel
reuses the existing `transfer-note.delete` permission. Milestone columns
`sentby_id`/`date_sent` and `receivedby_id`/`date_received` on `transfer_notes`
follow the same `<action>by_id`/`date_<action>` idiom as `createdby_id`/`date_created`
and `JournalEntry.postedby_id`/`date_posted`.

## Service Management (`module:service-management`)
`ServicePurchaseController`, `ServicePurchaseReturnController`,
`ServiceSaleController`, `ServiceSaleReturnController`.

## Sales & POS (`module:pos`, + `permission:pos.access` / `permission:order.*`)
Setup: `OrderTypeController`, `PaymentMethodController`, `OrderSourceController`,
`DiscountController`, `SaleTypeController`. Operations: `PosRegisterController`,
`PosRegisterSessionController`, `PosScreenController`, `OrderController` (service:
`OrderService`), `OrderReturnController` (service: `OrderReturnService`),
`CustomerPaymentController` (service: `CustomerPaymentService` — order-targeted
payments may not exceed remaining due; due/amount are compared at the
business `decimal_points` scale so amounts that display as equal, e.g.
Rs 10.61 vs Rs 10.61, are accepted).

**Register session open — tenant/cashier binding:**
`PosRegisterSessionService::open()` forces `business_id`, branch-scoped
`branch_id`, and `cashier_id` to the authenticated user for every non-Super-Admin
caller, so a crafted request cannot open a POS session under another tenant or
as another cashier — unless the caller holds `pos.register.open.any`, in which
case a different `cashier_id` may be supplied provided that target user is
within the caller's own business/branch scope (checked via
`userInBusinessBranchScope()`, same helper as everywhere else in this module).
Super Admin may still supply those fields explicitly. `resolveRegisterForUser()`
continues to require the selected register to match the (now server-enforced)
business/branch, and `open()` additionally rejects opening a manual-mode
register whose `assigned_user_id` is set to someone other than the target
cashier (Super Admin excepted) — a register "assigned" to one cashier in the
UI is now actually enforced as that cashier's dedicated till.

`PosRegisterSessionController` and `PosScreenController` also apply
`$this->middleware('permission:pos.access')` in their constructors (in addition
to the route-group middleware), matching the project convention so a future
route outside the group cannot ship ungated.

**Single-record branch-scoped authorization:** `userInBusinessBranchScope($user,
$business_id, $branch_id)` (`app/Helpers/CommonFunctions.php`) is the
single-record counterpart to `applyRoleScope()` — same role groupings (Super
Admin unrestricted; business-level roles need only a business match;
branch-level and branch-anchored mixed roles need both business **and**
branch to match), used wherever a controller authorizes one record instead of
scoping a listing query. `PosRegisterSessionController::close()`/`summary()`/
`printSummary()`/`addCashMovement()`/`void()` all use it: each requires either
`Auth::id() == $session->cashier_id` (the session's own cashier — not accepted
at all for `void()`, which is always a supervisory action) or the acting user
to be in scope of the session's business **and branch**, holding the relevant
permission (`pos.register.close`, `pos.register.report.view`,
`pos.register.cash-movement.manage`, `pos.register.void`). This closes what
was previously a business-wide (not branch-scoped) check, so a POS Manager
confined to one branch can no longer act on another branch's session just
because it's the same business. `PosRegisterController::store()` uses the same
helper to stop a branch-scoped role from creating/editing a register outside
their own branch, and to force `business_id` server-side for every
non-Super-Admin caller (previously trusted from the request body).

**Cash-in/cash-out authorization & idempotency:**
`PosRegisterSessionController::addCashMovement()`/`close()` both require either
`Auth::id() == $session->cashier_id` (the session's own cashier) or the acting
user to be in scope of the session's business **and branch** (see above) and
hold `pos.register.cash-movement.manage` / `pos.register.close` respectively.
`PosRegisterSessionService::addCashMovement()`
still independently re-checks the session is `status = 'open'` (defense in
depth if ever called from elsewhere). It's idempotent on `offline_local_id` —
the same column/pattern the desktop's offline sync already uses on this table
(`OfflinePushService::pushCashMovement()`) — reused here for the web POS
screen: `pos-screen.js` generates one client-side key per modal open
(`generateRequestId()`) and disables the submit button while the request is in
flight, so a double-click or a retried request resolves to the original
movement instead of creating a duplicate one. Every movement is also written
to `ActivityLog` via the `Auditable` trait (`module = 'pos_register_cash_movement'`),
recording the causer, business/branch, and old/new values alongside the row's
own `createdby_id`/`date_created`.

The same ownership rule is enforced a second time on the Offline Desktop POS
API, which reaches `close()`/`addCashMovement()` through two routes of its
own and previously had **no** object-level authorization on either (only the
device-token + `auth:sanctum` + coarse `permission:` middleware, none of
which checks whose shift is being acted on): the direct
`POST /api/offline/register-sessions/{close,cash-movement}` endpoints
(`RegisterSessionController`) and the batched `session.close`/
`session.cash_movement` transactions replayed by
`OfflinePushService::push()`. Both now carry their own
`authorizedForSession()` helper (same own-session-or-in-scope-permission check
as the web controller via `userInBusinessBranchScope()`, duplicated rather
than shared since the two classes authenticate differently) before calling
into `PosRegisterSessionService`. `pushSessionOpen()` needs no equivalent
duplication — it calls `PosRegisterSessionService::open()` directly, so the
`assigned_user_id`/`pos.register.open.any` enforcement described above already
applies to the offline path for free.

**Voiding a closed session:** `PosRegisterSessionController::void()`
(`POST pos-register-session/void`) wraps
`PosRegisterSessionService::reverse()` — a soft-delete of a closed session
plus an audit note, gated by `pos.register.void` with no owning-cashier
bypass (unlike close/cash-movement, this is always a supervisory action).
Registers don't touch stock/accounting directly, so voiding has no
compensating session or stock/JV interaction — it exists purely to correct
the operational record and is logged to `ActivityLog` (module
`pos_register_session`, action `voided`). `open()`/`close()`/`reverse()` on
`PosRegisterSessionService` and `save()`/`status()`/`delete()` on
`PosRegisterService` (module `pos_register`) are all now audited the same way
`addCashMovement()` already was, closing the gap where register CRUD and
session open/close/void previously left no trace of who performed them.

**Cash refund → shift reconciliation:** `PosRegisterSessionService::getSummary()`
computes `expected_cash = opening_cash + cash_sales - cash_refunds +
cash_movements_in - cash_movements_out - cash_expenses` (all four `cash_*`
figures are `payment_methods.type = 'cash'` only — a card/bank/wallet/store-credit
sale, refund, or expense never moves this number). `cash_refunds` sums approved
`order_returns` whose `refund_payment_method_id` is a cash method **and** whose
`pos_register_session_id` points at this session. That column is set once, at
approval time, by `OrderReturnService::resolveCashRefundSession()` (called from
`applyOrderReturnPosting()`), which is idempotent the same way the return's
journal entry is — `applyOrderReturnPosting()` no-ops entirely on a second
approval attempt if a JournalEntry already exists for the return, so a cash
refund can never be attributed twice. It tries, in order: (1) the approving
user's own open session, (2) the original order's own `register_session_id` if
that session is still open, (3) the single open session business-wide if there
is exactly one. Only when none of those resolve does the refund post to
accounting with no session link (and consequently doesn't reduce any shift's
expected cash) — this can happen for a back-office approval with several
registers open at once and no obvious owner.

**Offline Desktop POS API** (`routes/offline.php`, prefix `/api/offline`):
`App\Http\Controllers\Api\Offline\*` controllers delegate to
`App\Services\Concrete\Api\Offline\*` services. `OfflineSyncService` packages
bootstrap/incremental master data; `OfflinePushService::push()` replays a
device's queued `sync_queue` transactions (type strings set by the Electron
client — `order.complete`, `order.hold`, `session.open`, `session.close`,
`session.cash_movement`, `customer.add`, `expense.add` — these two names in
particular must stay in lockstep with the `type` values the desktop repo's
`electron/ipc/handlers.js` writes, since they're matched by exact string) through
existing `OrderService` / `PosRegisterSessionService` / `UserService` /
`ExpenseService` with `client_request_id` (orders) / `offline_local_id`
(sessions, cash movements, expenses) idempotency. Since the desktop only knows
its own locally-generated session id until that session's own `session.open`
transaction has synced, every other transaction referencing a session (orders,
close, cash movements, expenses) is resolved from local to server
`pos_register_session_id` via `OfflinePushService::resolveSessionServerId()`
before being handed to the underlying service — a transaction referencing a
session that hasn't synced yet fails (and is retried next cycle) rather than
being applied against the wrong record. Device rows live in `pos_devices`;
middleware `EnsureOfflinePosDevice` validates `X-Pos-Device-Id` +
`X-Pos-Device-Token`. The same `/api/offline/sync/push` endpoint also serves
the desktop's per-order "sync this order" / "sync all orders" actions (its
Pending Sync panel and separate Order History screen) — those send a
smaller, order-only `transactions` batch rather than the periodic scheduler's
full mixed one, but hit the identical route/controller/service. The Electron
client lives in the separate **`erp-desktop-pos`** repo
(`C:\xampp\htdocs\erp-desktop-pos` — Vue 3 + SQLite + sync engine). `exportSettings()`'s `thermal_print_setting` is manually
flattened from `ThermalPrintConfig` (`isEnabled()`/`paperWidthMm()`/
`fieldConfig()`/`footerConfig()`) into a plain array — that class has no public
properties, so returning it as-is JSON-encodes to `{}`. The desktop's Reports
panel prints its Register Session Summary client-side (no PDF/ESC-POS
pipeline): a hidden receipt block in `PosScreen.vue`, styled with the same
`tr-*` class names as `resources/views/admin/pos/register-session/print/
thermal-session-summary.blade.php` / `public/assets/css/print-thermal.css`, is
isolated via `@media print` and sent to the OS print dialog with
`window.print()` when `paper_width_mm` is synced from the setting above.

Customer receivable COA:
`CustomerService::upsertProfile()` (admin Users create/edit and API
`CustomerAccountService::ensureProfile()` / website signup) attaches
`accounting_settings.default_customer_account_id` to `customer_profiles.account_id`
on **create and update**, mirroring `SupplierService::save()` +
`default_supplier_account_id`. Changing either default in
`SettingService::updateAccountingSetting()` runs `syncDefaultAccount()` on
existing customers/suppliers. Customer-payment posting resolves the receivable COA
via `CustomerService::resolveCustomerReceivableAccountId()` (profile COA first,
then `default_customer_account_id`, validated as an active leaf account for the
business); `CustomerPaymentService::applyPosting()` re-resolves at post time inside
the same DB transaction — if neither source is valid, posting aborts with
`CustomerService::RECEIVABLE_COA_MISSING_MESSAGE` and no journal entry is created.

**Stock availability/validation in POS:** `OrderService::searchProducts()`,
`getProductsByCategory()` and `resolvePrices()` attach `is_track_stock`/
`available_stock` (current `ProductVariationStock.quantity` at the register's
warehouse) onto every variation they return, so `pos-screen.js` can show it
and block a cart quantity beyond it client-side. This is enforced
server-side in three places, all gated by `allowsNegativeStock()`
(`InventorySetting.negative_stock` - see
[Settings System](07-settings-system.md)): `saveLinesAndComputeTotals()`
(hold/draft save), `post()` (checkout - locked, authoritative, decrements
stock), and `revalidateStockOnResume()` (runs inside `resume()`, before a
held order's status flips back to draft - removes a line that's gone out of
stock and clamps one whose held quantity now exceeds what's available,
returning the adjustments as `stock_warnings` on the resumed order for the
cashier).

**POS screen layout (`resources/views/admin/pos/screen/index.blade.php`,
`public/assets/js/admin/pos-screen.js`, `public/assets/css/admin/pos-screen.css`):**
customer picker is a native compact `<select>` in the cart header (same styling as
Sale Type); payment method (default **Cash** via `selectDefaultPaymentMethod()`),
order discount, voucher, and delivery address sit in a collapsible panel toggled
by a side bookmark clip (`#posCheckoutToggle` on `#posCheckoutWrap`, collapsed by default — toggling adds/removes `.checkout-open`
on `#posMainCol` so the product grid flexes). Delivery order types show address +
payment method on one row inside that panel and auto-expand it
(`updateDeliveryAddressVisibility()`).

**Order status changes:** there is no generic status dropdown. Status moves via
dedicated actions: POS (`hold`, `resume`, `complete` → `post()`), Admin Order
Detail (`OrderController::changeStatus()` → `OrderService::changeStatus()` —
`POST admin/order/change-status`), and list-row Cancel (`order.cancel`). Posted/
cancelled/void delegate to `post()`/`cancel()`/`void()`; delivery fulfilment
steps (`shipped`, `out_for_delivery`, `delivered`) are lightweight
`transitionStatus()` updates on `DELIVERY` order type only (no second stock/GL
hit). Website hold orders have no `cashier_id`; POS Held Orders passes
`include_null_cashier=1` so they appear alongside the session cashier's own
holds. `CustomerOrderService::mapStatus()` maps ERP `posted` → storefront
`processing` so fulfilment steps can advance before `delivered`.

**Same-day POS order correction:** `OrderService::correct()` (route
`POST admin/order/correct`, permission `order.correct`) is the only path that
mutates a posted POS order in place. It does **not** widen `save()` (draft/hold
only). Flow: `assertCorrectable()` (posted, POS `register_session_id`,
`sale_date` = today, no `order_returns`, no `customer_payments`, accounting
period open) → snapshot for audit → `reversePostedEffects()` (shared with
`void()`: soft-delete `POS_SALE` JE, reverse SALE stock txs, reverse
voucher/store-credit) → rebuild lines/payments via
`rebuildPostedOrderCart()` / `saveLinesAndComputeTotals()` (identity fields
immutable) → `validatePaymentsForPosting()` + `applyPostedEffects()` (shared
with `post()`) → status stays `posted` → `order_status_history` + Activity Log
action `corrected` (old/new values, reason, `authorized_permission`).
`reopen()` remains cancelled→draft only. Offline API has no correct endpoint.
UI: Order Show / Order History / `?correct={order_id}` on the POS screen.
`OrderCorrectionReportController`/`OrderCorrectionReportService` (route prefix
`admin/reports/order-correction-report`, permission
`reports.order-correction-report.*`) is a read-only report over these same
`activity_logs` rows (module `order`, action `corrected`) - it does not touch
`orders` directly, joining `record_id` back to `Order` only for display
(order/branch/business filters + role scope on `activity_logs.business_id`/
`branch_id`). `old_values`/`new_values` are enriched with product/variation/
payment-method names for the "View Changes" before/after diff in
`admin.reports.order_correction.index`.

## Accounting (`module:accounting`)
Core: `AccountTypeController`, `AccountSubTypeController`,
`ExpenseCategoryController`, `AccountController`, `JournalController`,
`JournalEntryController`, `BankReconciliationController` (service:
`BankReconciliationService` — reconciliation sessions over COA cash/bank accounts
and posted `journal_entry_details`; statement import + 1:1 match; no new JEs),
`RecurringTransactionController`, `VoucherController`
(service: `VoucherService` — the full promotional rule engine: percent/fixed/
BOGO/buy-X-get-Y, scheduling, min-order/max-discount, usage limits, and
product/category/brand/variation/customer/branch/sale-type/order-type/order-
source/payment-method targeting; `isApplicable()` is the single eligibility
gate and `calculate()`/`eligibleForCart()` are called from
`OrderService::saveLinesAndComputeTotals()` and the POS "browse"/"apply"
endpoints — see `OrderController::eligibleVouchers()`/`previewVoucher()`).
`DiscountController` (Sales & POS, service: `DiscountService`) stays a simple
named flat-rate discount with no conditions - deliberately not merged with
Voucher's rule engine (see `resources/docs/developer/02-database-schema.md`).
Advanced (`App\Http\Controllers\Admin\Accounting`): `FiscalYearController`,
`AccountingPeriodController` (service: `AccountingPeriodService`),
`PeriodClosingRuleController`, `BudgetController`. Expenses: `ExpenseController`,
`AdminExpenseController`. Fixed Assets (accounting PPE, distinct from HRM
`Hrm\AssetController`): `FixedAssetCategoryController`, `FixedAssetController`,
`FixedAssetDepreciationController` (services: `FixedAssetCategoryService`,
`FixedAssetService`, `FixedAssetDepreciationService`,
`FixedAsset\FixedAssetAccountingService`, `FixedAsset\FixedAssetCalculator`).
`FixedAssetService::pause()`/`resume()` toggle `FixedAssetStatuses::PAUSED`/
`ACTIVE` (paused assets are skipped by the depreciation cron); `dispose()`
retires an asset via `FixedAssetDisposalTypes` (sale/waste/damage/theft/
write_off/other), computing gain/loss against current book value for a sale
and posting the disposal JV through `FixedAssetAccountingService::postDisposal()`
using the `AccountingSetting` gain/loss-on-disposal accounts (idempotent per
`source_type`+`source_id`). Depreciation cron: `fixed-assets:post-depreciation`
daily at 00:15 (idempotent per `fixed_asset_id`+period, catches up missed
periods). Also two
ungated cross-cutting popup controllers reused from Orders/Purchases/Expenses:
`JournalVoucherViewController`, `StockConsumptionViewController`.

## HRM (`module:hrm`) — `App\Http\Controllers\Admin\Hrm`
`DepartmentController`, `DesignationController`, `ShiftController`,
`EmployeeController`, `AttendanceController`, `LeaveTypeController`,
`LeaveRequestController`, `SalaryComponentController`,
`EmployeeSalaryStructureController`, `EmployeeAdvanceController`,
`EmployeeDeductionController`, `EmployeeLedgerController`,
`EmployeeExitController`, `AssetController`, `AssetAllocationController`, plus
Employee Self-Service under `Hrm\Ess`: `EssDashboardController`,
`EssAttendanceController`, `EssLeaveController`, `EssPayslipController`,
`EssProfileController`, `EssAdvanceController`, `EssExitController`.

## Payroll (`module:payroll` — independent toggle from `hrm`)
`Hrm\PayrollController` (service: `Hrm\PayrollService`), `Hrm\PayslipController`.

## Reports (cross-domain, `App\Http\Controllers\Admin\Reports`)
Uniform `index / data / print / pdf / export / export-csv` shape registered via a
loop over a controller map in `routes/web.php`. ~90 report classes across HRM
(`Reports\Hrm\{Employee,Attendance,Leave,Lifecycle}`, `module:hrm`), Payroll
(`Reports\Hrm\PayrollFinance`, `module:payroll`), Procurement (`module:inventory`),
Customer/POS (`module:pos`), Orders/POS sales
(`Reports\Orders\*`, `BaseOrderReportController` /
`BaseOrderReportService`, also `module:pos`), Service Management
(`module:service-management`), and Accounting/Financial (`module:accounting` —
includes statement reports Profit & Loss, Balance Sheet, and Cash Flow). See
[Reports Infrastructure](06-reports-infrastructure.md).

## Settings (Core)
`SettingController` (service: `SettingService`) — `admin/setting`, one controller
with a per-domain update action (business, accounting, inventory, customer,
supplier, email, SMS, WhatsApp, FBR, POS, PRA, print, thermal-print, barcode,
theme, notification).

## Documentation (Core — this system)
`DocumentationController` (service: `DocumentationService`) — `admin/documentation`.
See [The Documentation System Itself](12-documentation-system.md).

## Audit, Security & Notifications (Core)
`ActivityLogController`, `LoginHistoryController`, `NotificationController` —
`admin/activity-log`, `admin/login-history`, `admin/notifications`.

`ActivityLogController@index` sources its Module and Action filter option lists
by running `ActivityLog::distinct()` on the `module`/`action` columns (cached
5 min via `Cache::remember`) instead of a hand-maintained array, so the
dropdowns can't drift out of sync as new `logActivity()` call sites are added —
see `App\Traits\Auditable` and `App\Models\ActivityLog::prettifyLabel()` (the
`ucfirst(str_replace(['_','-'], ' ', $value))` label formatter, shared with
`ActivityLogService::getData()`'s module/action DataTable columns). The screen
also filters by `causer_id` (a business-scoped user dropdown, hidden for
Superadmin the same way `OrderCorrectionReportController@index` scopes its
`managers` list) and by an exact-match `record_id` text filter, in addition to
business/branch/module/action/date. There is no update or destroy route/method
for `activity_logs` anywhere in the app — it is intentionally append-only.

Two action-naming fixes worth knowing when querying `activity_logs` directly:
`OrderService::recordStatusHistory()` now logs order void/cancel as their own
distinct actions (`voided` / `cancelled`) rather than the generic
`status_changed` it used for every non-`posted` transition before — do not
assume `status_changed` still covers void/cancel in older reporting queries.
`StockTakingService::save()`/`delete()` now also call `logActivity()` (module
`stock-taking`, actions `created`/`updated`/`deleted`) — previously only the
approve/reject transition (`status()`) was logged, leaving the count entry
itself untraced.

## Push Notifications (FCM)
`NotificationTemplateController`, `BroadcastNotificationController` —
`admin/notification-template`, `admin/broadcast-notification`.
Firebase credentials: Settings → Firebase tab (`SettingController::updateFirebaseSetting`,
`SettingService::getFirebaseSetting` / `updateFirebaseSetting`).
Reusable sender: `App\Services\Concrete\Firebase\FirebaseNotificationService`.
Queued worker: `ProcessBroadcastNotificationJob`. See
[FCM Broadcast Notifications](13-fcm-broadcast-notifications.md).

## Shared Concerns
`App\Http\Controllers\Admin\Concerns\HasLookupTypeCrudActions` — shared trait for
simple lookup-type CRUD controllers (e.g. Order Type/Source, Payment Method) to
avoid duplicating index/store/status boilerplate.
