# Serial Number Tracking

Optional, per-variation unit-level tracking layered on top of the existing
aggregate stock system (`product_variation_stocks`). Follows the same
architectural shape as **Batch/Expiry Tracking** — an opt-in flag on
`ProductVariation`, a sub-ledger table, and integration points wired into
every stock-moving module — but a serial is 1-row-per-physical-unit rather
than an aggregated quantity per batch-no, so it uses a sibling pair of tables
rather than reusing `product_variation_batches`.

## The two tables

`ProductVariation.track_serial_number` (boolean, default `false`,
`2026_09_04_150000_add_track_serial_number_to_product_variations_table.php`)
is the opt-in flag, set from the same variation-modal checkbox group as
`track_batch`/`track_expiry` in `resources/views/admin/product/create.blade.php`.

| Table | Purpose |
|---|---|
| `product_variation_serial_numbers` | **Current state** — one row per physical unit. `serial_no` (hard-unique per `business_id`, enforced by a DB unique index — a serial number is never reused, even after the row's lifecycle ends), `status` (`available`/`in_transit`/`sold`/`returned_to_supplier`/`damaged`/`wasted`/`expired`/`under_repair`/`replaced`/`decommissioned` — see `App\Enums\SerialStatus`), `warehouse_id` (the unit's last-known physical location — deliberately **not** cleared on sale/transit/return-to-supplier; `status` alone is what excludes a unit from "available" pools, so the location stays informative even for a sold/in-transit unit), `avg_price`, `source_reference_type`/`source_reference_id`/`source_detail_id` (which Purchase/GRN/Opening Stock line created it), `current_order_id`/`current_order_detail_id`/`current_customer_id` (set only while `status = sold`), `warranty_expires_at`. |
| `product_variation_serial_movements` | **Append-only audit trail** — the per-unit analogue of `product_variation_stock_transactions`. One row per lifecycle event (`App\Enums\SerialMovementEventType`: `purchased`, `opening_stock`, `transfer_sent`, `transfer_received`, `sold`, `sale_returned`, `purchase_returned`, `damaged`/`wasted`/`expired`, `sent_for_repair`, `returned_from_repair`, `replaced`, `decommissioned`, `added_manually`), carrying `from_warehouse_id`/`to_warehouse_id` and a free-form `reference_type`/`reference_id` (e.g. `'order_detail'` + an `order_detail_id` — these are plain strings, not `App\Enums\ReferenceType` values, since a serial's reference vocabulary is per-unit-event, not per-ledger-transaction). Powers both the Serial Number Details page timeline and the Movement History report. |

No pivot tables were added for purchase/order/transfer/return/waste line
associations. Each serial has exactly one *current* reference at a time
(the columns above); full history — including reversed/past associations —
is reconstructed from the movements log by `reference_type` + `reference_id`
when needed (e.g. a Purchase Return's serial picker queries
`source_detail_id` directly on the current-state table rather than a pivot).

## `ProductVariationSerialService`

`app/Services/Concrete/Admin/ProductVariationSerialService.php` owns every
status transition and is the **only** writer of both tables. Every
stock-moving module calls into it *alongside*, not instead of, its existing
`ProductVariationStockService`/`ProductVariationStock` aggregate-quantity
math — the aggregate `quantity` column stays the authoritative stock level;
serial rows are an additional, validated ledger that must reconcile to the
same count (enforced by count-matching, not derived from it).

Lifecycle methods (each wrapped in `lockForUpdate()` where they mutate),
called from the modules listed in the table further down:

- `receiveSerials(...)` / `reverseReceivedSerials(...)` — create serials on
  receipt (`available`); reversal is a genuine **hard delete** (including the
  movement rows), not a status change, so the serial number becomes reusable
  — but only while every serial from that source is still untouched
  (`available`); throws otherwise, blocking the reversal.
- `sendForTransfer(...)` / `receiveTransfer(...)` / `cancelTransferSend(...)`
  — `available → in_transit → available` (destination warehouse); cancel
  reverses an in-transit-but-never-received send.
- `allocateForSale(...)` / `releaseFromSale(...)` — `available → sold`,
  stamping order/customer/warranty; release is the order-void counterpart.
- `restockFromReturn(...)` / `cancelSaleReturn(...)` — Sales Return
  approve/reverse (`sold → available` and back, re-attaching the original
  order/customer on reversal).
- `returnToSupplier(...)` / `cancelSupplierReturn(...)` — Purchase Return
  approve/reverse (`available → returned_to_supplier` and back).
- `markLoss(...)` / `cancelLoss(...)` — Waste/Damage/Expiry approve/reverse,
  mapping `App\Enums\LossType` onto `SerialStatus::DAMAGED/WASTED/EXPIRED`.
- `sendForRepair(...)` / `returnFromRepair(...)` / `replaceSerial(...)` —
  lightweight, status/log-only actions surfaced on the Serial Number Details
  page (not a full workflow engine) for the optional warranty/repair
  requirement. `replaceSerial()` retires one unit and, if it was sold, hands
  the same order/customer a replacement unit from stock.
- `addFoundUnit(...)` — the one method that **also** touches
  `ProductVariationStock`/`ProductVariationStockTransaction` directly (a
  positive adjustment, `TransactionType::ADJUSTMENT` /
  `ReferenceType::MANUAL`) — for a physically-found unit that was never
  entered into the system (e.g. resolved manually after a Stock Taking
  discrepancy). Every other method only moves serial-ledger status; the
  aggregate math for those stays owned entirely by the calling module.
- `availableSerialsFor(...)` / `serialsCurrentlySoldUnder(...)` /
  `availableSerialsFromSource(...)` — read-only pickers feeding every
  selection UI (POS, Purchase Return, Transfer send, Waste/Damage/Expiry,
  Sales Return).
- `getData(...)` / `getFullDetails(...)` — back the Serial Number search
  screen and Details page (see below).

## Integration points

Each stock-moving module gained a `serial_numbers` JSON column on its detail
table (`purchase_details`, `good_receipt_note_details`,
`opening_stock_details`, `purchase_return_details`, `order_details`,
`order_return_details`, `waste_damage_expiry_details`), staged at save time
and consumed at posting/approval time — mirroring exactly how `batch_no` is
already staged/consumed for batch-tracked lines. Transfer Note has no detail
column since its serials are selected at the **send**/**receive** action
itself, not at draft-creation time (see below).

| Module | Where wired | Serial UI |
|---|---|---|
| Purchase (direct) | `PurchaseService::save()/applyDirectPurchaseApproval()/reverseDirectPurchaseApproval()` | Purchase create screen: a "Serial #" column per line opens a modal — type/scan exactly `received_quantity` serial numbers (reuses `admin.partials.barcode_scanner`). |
| GRN | `GrnService::save()/applyGrnApproval()/reverseGrnApproval()` | Same pattern on the GRN receiving screen. |
| Opening Stock | `OpeningStockService::save()/applyOpeningStockPosting()/reverseOpeningStockPosting()` | Same pattern on the Opening Stock screen. |
| Purchase Return | `PurchaseReturnService` (`save()`, `applyPurchaseReturnPosting()`, `reversePurchaseReturnPosting()`) | Return line's serial picker is a **checkbox list of what's actually available**, fetched from `PurchaseReturnController::availableSerials` (`getAvailableSerialsForPurchaseDetail()`, scoped to that purchase/GRN line via `source_detail_id`) — not free typing, since you're selecting existing units. |
| Stock Transfer | `TransferNoteService::send()/receive()/reverseTransferOutPosting()` | `send()`/`receive()` gained an optional `$serials_by_detail_id` / per-line `serial_numbers` parameter (backward compatible — omitted entirely for non-serial transfers, so the existing single-click Send flow is untouched when nothing on the note is serial-tracked). The index screen's Send/Receive modals fetch pickers from `TransferNoteController::availableSerialsForSend`/`inTransitSerials`. |
| POS / Sales | `OrderService::saveLinesAndComputeTotals()` (validates count == quantity), `applyPostedEffects()`/`reversePostedEffects()` (`allocateForSale()`/`releaseFromSale()`) | `pos-screen.js`: a serial-tracked product routes `addProductToCart()` through `openSerialPickerForAdd()` instead of the normal qty-bump path — quantity is *derived* from how many serials are checked, never entered independently, which is what keeps a cart line's serial count and quantity from ever drifting apart. An existing cart line gets a "Manage Serials" button (`openSerialPickerForEdit()`) in place of the qty stepper. New `OrderService::getAvailableSerials()` / `OrderController::availableSerials` (`admin/order/available-serials`) feeds the picker, resolving warehouse via the same `resolveWarehouseContext()` (register session) every other POS lookup uses. Because the storefront/mobile checkout (`WebsiteCheckoutService`/`MobileCheckoutService`) creates orders via this same `OrderService::save()`/`post()` pipeline and only decrements stock when an admin posts the order, no separate storefront serial-picker was needed — the admin who posts the order picks serials at that point, same as POS. |
| Sales Return | `OrderReturnService` (`save()`, apply/reverse posting) | Picker lists serials **currently sold under that order line** (`serialsCurrentlySoldUnder()` / `OrderReturnController::soldSerials`), not a free-type field. |
| Waste/Damage/Expiry | `WasteDamageExpiryService::save()/applyPosting()/reversePosting()`, `getSerials()` (mirrors the existing `getBatches()`) | Manual picker, same shape as the existing batch dropdown — the user is recording a *known* unit as lost, not an auto-pick. |
| Stock Taking | `StockTakingService::save()` | **Read-only for serial-tracked lines** (a deliberate scope decision, not a technical limitation) — the physical-quantity input is disabled and forced server-side to always equal system quantity, so a stock-taking session can never desync the serial ledger from the aggregate. A discrepancy in a serial-tracked line is reconciled through the Serial Number screens instead (mark a specific unit lost/damaged via Waste/Damage/Expiry, or record a found one via "Add Found Unit"). |

## Serial Number module (search + details)

`SerialNumberController` (`admin/serial-number`) — a DataTables search screen
(`ProductVariationSerialService::getData()`, filterable by product/variation/
warehouse/status) and a Details page
(`admin/serial-number/{id}` → `getFullDetails()`) showing product/variation,
current status/location, purchase info (`source_reference_type`/`_id`
resolved through the existing `ReferenceResolverService::resolveDocNo()`),
sale info + customer, warranty, and the full movement timeline. Quick actions
on the Details page: Send/Return Repair, Replace This Unit (all
lifecycle-only, no accounting impact), and a link out to **Create
Waste/Damage/Expiry** for "mark lost/damaged" rather than a duplicate
ad-hoc posting path — keeping the one accounting-impacting write-off flow
(with its Stock Loss Voucher JV) centralized in `WasteDamageExpiryService`
instead of forking it. "Add Found Unit" (index screen) is the one
serial-number action that **does** move the aggregate stock (see
`addFoundUnit()` above), for a unit that was physically present but never in
the system.

Lookup endpoints follow the existing `ProductVariationBatchController::byProduct/byVariation/byWarehouse`
convention (`admin/serial-number/lookup`, `admin/serial-number/by-variation/{id}`)
rather than a public REST API — matching how every other internal picker
endpoint in this codebase (barcode lookup, batch lookup, product search) is
a plain session-authenticated admin route, not `routes/api.php`.

## Reports

Five reports under `App\...\Reports\Inventory`, all extending
`BaseInventoryReportController` and reusing the generic `InventoryReportExport`
for Excel/CSV, exactly like every other Inventory report:

- **Serial Number Register** / **Available Serial Numbers** / **Sold Serial
  Numbers** — all three share one service, `SerialNumberReportService`,
  distinguished only by a `$forcedStatus` constructor argument (`null` for
  Register, `SerialStatus::AVAILABLE`/`SerialStatus::SOLD` for the other
  two) — they're the same underlying query over
  `product_variation_serial_numbers` with a different status filter, so each
  gets its own controller/permission/view (per the registry) but not its own
  service class.
- **Serial Number Movement History** — `SerialNumberMovementReportService`,
  reading `product_variation_serial_movements` directly.
- **Customer-wise Serial Numbers** — `SerialNumberCustomerReportService`,
  sold units joined to `users`/`orders`, filterable by customer.

All five use the shared `AppliesInventoryReportScope` trait; `applyRoleScope()`
is called with explicitly-qualified `business_id`/`branch_id` columns
(`product_variation_serial_numbers.business_id`, etc.) rather than the bare
defaults, since these queries join multiple tables (`orders`, `warehouses`)
that also carry `branch_id` — the bare unqualified default would be
ambiguous and error under MySQL. `product_variation_serial_movements`
gained its own `branch_id` column
(`2026_09_05_090000_add_branch_id_to_product_variation_serial_movements_table.php`)
for the same reason — every other domain table already carries one for this
exact scoping mechanism, and it had been missed on the initial migration.

## Permissions & routes

Module key `serial-number` (`view`/`create`/`edit`/`delete`/`status`) in
`PermissionRegistry`'s Inventory block, plus a `reports.serial-number-*`
block (5 reports × view/print/pdf/export/export-csv = 25 permissions).
Registered in `operationalModuleKeys()` and the Inventory
Manager/Operation Manager role lists in `RoleDefaultPermissions`; Business
Admin/Branch Admin get it automatically (every permission here is
`is_system = false`). All routes sit inside the existing
`module:inventory` route group — no new subscription-tier gating key, same
as Batches/Waste-Damage-Expiry.

## Whole-unit quantities

There is no dedicated "must be a whole number" validation added anywhere in
the unit-conversion system for serial-tracked variations — deliberately
scoped out (see the plan's "Scope note" — touching the separate Unit
Conversion module for this was judged disproportionate). In practice this is
a non-issue: every serial-count validation in every integration point above
requires the submitted `serial_numbers` array's length to exactly equal the
line's quantity, which is only satisfiable by an integer quantity to begin
with.
