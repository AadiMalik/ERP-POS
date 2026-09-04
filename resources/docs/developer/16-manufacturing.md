# Manufacturing & Production

Recipe/BOM, Manufacturing Plan (reservation only), and Production (the only
thing that moves stock) built entirely on the existing stock/batch/ledger/
accounting machinery — `ProductVariationStock`, `ProductVariationBatch`,
`ProductVariationStockTransaction`, the FEFO/FIFO picker, the moving-average
costing formula, and the package/module-gating pattern. The schema had
already reserved `TransactionType::PRODUCTION_IN`/`PRODUCTION_OUT`,
`ReferenceType::PRODUCTION`/`CONSUMPTION`, and
`JournalSourceTypes::PRODUCTION`/`PRODUCTION_CONSUMPTION`/
`PRODUCTION_FINISHED_GOODS` before this module existed.

Gated `module:manufacturing` (umbrella) with child modules `recipe`,
`manufacturing-plan`, `production` (each also independently gated, and each
requires the umbrella too per `FeatureLimitService::hasModule()`'s parent
walk). Package-tier gate only — there is no business-level `ManufacturingSetting`
table/toggle; it was deliberately removed as unnecessary once the module was
trimmed down to its minimal spec below (same pattern as HRM/Payroll).

## Deliberately minimal by design

This module went through two rounds of simplification versus its first
build. The rule that survived both passes: **only what the business actually
asked for exists** — no recipe versioning, no wastage %, no output/yield
quantity, no by-products, no multi-step production execution, and no
overproduction override. If a future request needs one of these, it's an
intentional addition, not a restoration of removed dead code.

- **One recipe per finished variation, edited in place.** `product_recipes`
  has a DB-level `unique(product_variation_id)` constraint
  (`recipe_one_per_variation_unique`). There is no recipe list page — the
  only Recipe UI is a single create page (`admin/recipe`) that, on selecting
  a product+variation, loads the existing recipe's lines (if any) via
  `GET admin/recipe/for-variation/{id}`. Each line is its own AJAX round trip,
  saved the instant its popup's Save button is clicked (`POST admin/recipe/item`
  → `ProductRecipeService::addItem()`, lazily creating the recipe header the
  first time a line is added for that variation) and removed the instant its
  row's trash button is clicked (`DELETE admin/recipe/item/{id}` →
  `ProductRecipeService::removeItem()`) — there is no separate "Save Recipe"
  submit step, no version/status/effective-date concept, and nothing ever
  archives a recipe row.
- **Recipe lines carry their own warehouse.** `product_recipe_items.warehouse_id`
  — not the Manufacturing Plan. `manufacturing_plans` has no `warehouse_id`
  column; each `manufacturing_plan_materials` row gets its warehouse from the
  recipe item it was exploded from. Each **Production** independently picks
  its own finished-goods warehouse.
- **Recipe quantities are always the raw material's own base unit** — no
  unit-selection/conversion step anywhere in Recipe/Plan/Production math.
  `ProductRecipeService::addItem()` stamps `unit_id = rawVariation->base_unit_id`
  on every line server-side regardless of what's posted.
- **Overproduction is a hard, unconditional block** — a Production's
  `quantity` can never exceed its plan's `remaining_quantity`. No override,
  toggle, or permission.
- **`productions.batch_no` is always system-generated** —
  `ProductionService::save()` stamps it to the production's own
  `production_no` on create; the form never accepts one.

## Schema

Migrations `2026_09_04_090000`–`2026_09_04_090700` (initial build), then four
simplification migrations applied on top —
`2026_09_04_100000_simplify_manufacturing_module` (adds
`product_recipe_items.warehouse_id`, drops `manufacturing_plans.warehouse_id`,
drops `productions.is_overproduction`/`overproduction_reason`, drops
`manufacturing_settings` entirely), `2026_09_04_110000_adjust_manufacturing_plan_master_fields`
(adds `manufacturing_plans.plan_date`/`is_complete`/`approvedby_id`, drops
`planned_start_date`/`planned_end_date`/`notes`),
`2026_09_04_120000_strip_manufacturing_to_minimal_spec` (drops
`production_steps`, `production_byproducts`, `product_recipe_steps` tables
entirely; drops `name`/`version`/`previous_version_id`/`status`/
`effective_from`/`effective_to`/`output_quantity`/`output_unit_id`/
`is_default`/`notes` from `product_recipes`; drops `item_type`/
`wastage_percentage`/`wastage_quantity`/`sequence`/`notes` from
`product_recipe_items`; drops `productions.started_at`; adds the
`recipe_one_per_variation_unique` constraint), and
`2026_09_04_120100_simplify_manufacturing_status_enums` (collapses both
status columns to their final small enum lists via raw `ALTER TABLE MODIFY
COLUMN`, with existing rows remapped first):

| Table | Purpose |
|---|---|
| `product_recipes` | Recipe/BOM header, minimal: `business_id`, `product_id`, `product_variation_id` (finished good), audit columns, `is_deleted`. One row per `product_variation_id` (unique constraint) — no name, version, status, or effective dates. |
| `product_recipe_items` | BOM lines: `raw_material_product_id`, `raw_material_product_variation_id`, `quantity`, `unit_id` (always the raw material's base unit, server-stamped), `warehouse_id` (required, validated to belong to the recipe's business — see Design above). |
| `manufacturing_plans` | Intent + reservation only — **never** writes a stock transaction. Master-table fields: `manufacturing_plan_id`, `plan_no`, `plan_date`, `business_id`, `branch_id`, `product_id`, `product_variation_id`, `product_recipe_id` (locked at create), `planned_quantity`, `produced_quantity` (running total, recomputed), `status` (`draft`/`not_complete`/`completed`/`cancelled` — `App\Enums\ManufacturingPlanStatus`), `is_complete` (boolean, mirrors `status === completed`), `confirmed_at`, `approvedby_id`, full audit set (`createdby_id`/`updatedby_id`/`deletedby_id`/`date_created`/`date_updated`/`date_deleted`/`is_deleted`). `remaining_quantity`/`progress_percentage` are `Model` accessors, not stored columns. No `warehouse_id` (see Design above), no `planned_start_date`/`planned_end_date`/`notes` (dropped — the plan only needs a single `plan_date`). |
| `manufacturing_plan_materials` (child of `manufacturing_plans`) | One row per raw material exploded from the plan's locked recipe at `confirm()` time, `warehouse_id` copied from the recipe item: `required_base_quantity`, `reserved_quantity` (equals required unless later reduced by a cancel), `consumed_quantity` (running total actually drawn down by completed Productions). `outstanding_reserved_quantity` accessor = `reserved - consumed`. |
| `productions` | One independent production run/batch against a plan. `warehouse_id` (finished-goods destination, validated independently), `batch_no` (system-generated, see Design), `manufacturing_date`, `expiry_date` (optional, plain user input), `quantity`, `status` (`draft`/`completed`/`cancelled` — `App\Enums\ProductionStatus`). Cost fields: `material_cost` (computed), `labor_cost`/`overhead_cost`/`other_cost` (user input), `total_cost` = sum, `unit_cost` = `total_cost / quantity`. |
| `production_consumptions` (child of `productions`) | One row per raw-material batch actually drawn down by a completed Production — `product_variation_stock_transaction_id` links to the ledger row it caused. Joined with `productions`/`manufacturing_plans`, this answers both forward traceability (finished batch → production → plan → raw material batches consumed) and backward traceability (raw material batch → productions that consumed it). |

New columns on existing tables:
- `products`/`product_variations.is_purchasable`/`is_sellable`/`is_raw_material`/`is_manufactured` — independent booleans, still present on the schema but **no longer read anywhere in the Manufacturing module** (removed from `ProductRecipeService::addItem()`'s validation and from the product form's checkboxes per explicit product decision — every product/variation can be used as a raw material and/or as a manufactured good, no flag needed; the only recipe-line restriction is self-consumption, see below). `is_purchasable`/`is_sellable` remain in active use elsewhere; `is_raw_material`/`is_manufactured` are inert leftover columns.
- `product_variation_stocks.reserved_quantity` — the one genuinely new stock-model primitive this module needed (no reservation concept existed anywhere in the codebase before). **Available/free stock is always `quantity - reserved_quantity`**, computed inline wherever it's checked (no stored "available" column). Surfaced in the admin Stock listing (`Reserved`/`Available` columns, `ProductVariationStockService::getData()`) and in the Stock Ledger Report's balance summary (`StockLedgerQueryService::singleItemBalance()`).

Removed entirely in the simplification passes (do not reintroduce without a
new explicit request): `product_recipe_steps`, `production_steps`,
`production_byproducts` tables; `RecipeStatus`, `RecipeItemType`,
`ProductionStepStatus` enums; `ProductRecipeStep`, `ProductionStep`,
`ProductionByproduct`, `ManufacturingSetting` models.

## Reservation: the one new primitive

`reserved_quantity` lives directly on `ProductVariationStock` (not a side
ledger) and is **metadata only** — confirming/cancelling a plan or completing/
cancelling a production never writes a `product_variation_stock_transactions`
row for the reservation itself, only for the actual movement at Production
`complete()` time.

Because reservation had to be introduced globally, **`OrderService`'s and
`TransferNoteService`'s existing available-stock checks were patched** to
subtract `reserved_quantity` (both the fast-fail pre-check and the locked
authoritative re-check in each):
- `OrderService::getAvailableStock()` and the locked re-check inside
  `applyPostedEffects()`.
- `TransferNoteService::getAvailableQuantity()` and the locked re-check inside
  `send()`.

`reserved_quantity` defaults to `0` and nothing outside Manufacturing ever
writes to it, so this is a no-op for every business that never enables the
module — `quantity - 0 >= requested` behaves identically to the pre-existing
`quantity >= requested` check.

## `App\Traits\ValidatesWarehouse`

Extracted from `OrderService::assertValidWarehouse()` (word-for-word,
including the "a warehouse's `branch_id` may be null = shared across every
branch" rule) so `OrderService`, `ManufacturingPlanService::confirm()` (per
recipe-item warehouse), and `ProductionService::save()`/`complete()` all
validate a chosen warehouse the same way. `OrderService` now `use`s the trait
instead of defining its own copy.

`WarehouseService::getAllForCurrentUser()` is the form-facing counterpart: a
business-level role sees every active warehouse of their business; a
branch-level role only sees their own branch's warehouses plus shared
warehouses (`branch_id IS NULL`) — same rule `assertValidWarehouse()`
enforces server-side. Used by the Recipe line-item modal and the Production
create form's warehouse selects.

## `Manufacturing\ProductRecipeService`

- `getForVariation($variationId)` — the single recipe for that variation
  (with `items`), or `null`. Powers both the create page's "load existing
  recipe on select" behavior and `ManufacturingPlanController::recipeForVariation()`.
- `addItem($obj)` — the "Add Raw Material" popup's Save button calls this
  directly (one AJAX round trip per line, no batching): lazily creates the
  recipe header the first time a line is added for that
  `product_variation_id` (no versioning branch — there's only ever one header
  per variation), then creates exactly one `product_recipe_items` row.
  Validates: quantity is positive; the line cannot reference the recipe's own
  finished product/variation (no self-consumption — the only restriction on
  which product/variation can be a component; any other product/variation in
  the business is eligible, no `is_raw_material` flag check); the warehouse
  belongs to the recipe's business. Stamps `unit_id` to the raw material's
  base unit server-side. Returns the created item (with relations loaded) so
  the page can refresh its table without a full reload.
- `removeItem($product_recipe_item_id)` — the row's trash button calls this
  directly; deletes exactly that one line, nothing else.
- `isInUse($productVariationId)` — informational only (used for a UI warning
  before editing a recipe already referenced by a plan); it does **not**
  block or branch `addItem()`/`removeItem()`, since there is no versioning to
  protect.

## `Manufacturing\ManufacturingPlanService`

- `save($obj)` — creates/updates a `draft` plan. On create: generates
  `plan_no`, defaults `plan_date` to today, `status = DRAFT`, `is_complete =
  false`, `produced_quantity = 0`. Only a `DRAFT` plan can be edited.
- `confirm($id)` — the only place reservation happens. Locks the plan row,
  loads the locked recipe's `items` relation, and per item: asserts the
  recipe item's `warehouse_id` is set and valid (`ValidatesWarehouse`),
  computes `required = item.quantity * plan.planned_quantity` (straight
  multiply — no yield/wastage/unit-conversion math), locks the raw
  material's `ProductVariationStock` row **at that item's warehouse**,
  checks `quantity - reserved_quantity >= required` (throws naming the exact
  shortfall otherwise), increments `reserved_quantity`, and writes one
  `manufacturing_plan_materials` row (warehouse copied from the recipe
  item). Sets `approvedby_id`, moves plan to `NOT_COMPLETE`.
- `cancel($id, $reason)` — calls `releaseReservations()` and marks
  `CANCELLED`.
- `releaseReservations(ManufacturingPlan $plan)` — for every material row,
  releases `reserved_quantity - consumed_quantity` (the outstanding,
  not-yet-actually-used portion) back onto the stock row's
  `reserved_quantity` **at that material's own warehouse**, locked, and
  syncs the material row's `reserved_quantity` down to its
  `consumed_quantity`. Called by `cancel()` and, per-material, by
  `ProductionService::cancel()` when voiding a completed production.
- `recomputeProgress($id)` — recomputes `produced_quantity` (sum of
  `completed` productions' `quantity`) and derives `status`/`is_complete`
  (`COMPLETED` if `produced >= planned_quantity`, else `NOT_COMPLETE`) —
  **only `CANCELLED` is treated as a true terminal state that's never
  overwritten**; `COMPLETED` is recomputed like any other non-cancelled
  status, so voiding a completed Production correctly drops the plan back to
  `NOT_COMPLETE`. Called after every Production `complete()`/`cancel()`.
- `getEligibleForProduction($filters)` — plans in `NOT_COMPLETE` (role/business
  scoped), each with its `remaining_quantity` — powers the Production create
  form's plan dropdown.

## `Manufacturing\ProductionService`

- `save()` — hard-blocks `quantity > plan.remaining_quantity` (no override)
  and blocks unless the plan is `NOT_COMPLETE`; resolves the finished-goods
  warehouse via `ValidatesWarehouse`; on create, stamps `batch_no =
  production_no` (system-generated, see above) and status `DRAFT`.
- **`complete($id)`** — the only method that writes
  `product_variation_stock_transactions`. Inside one `DB::transaction`, locks
  the production and the parent plan. Iterates the plan's own
  `manufacturing_plan_materials` reservation snapshot directly — **never**
  the recipe's current live `items`, which can be edited or have lines
  removed at any point after the plan was confirmed against them (recipes
  are edited in place, see Design above); reading live items here would let
  an unrelated later recipe edit break approval of an already-reserved
  production. Per plan-material row: locks it under `lockForUpdate()`, reads
  **its** `warehouse_id`, computes the required base quantity by scaling
  `required_base_quantity` (the total reserved for the plan's whole
  `planned_quantity`) down to a per-unit rate and back up to
  `production.quantity` (`required_base_quantity / plan.planned_quantity *
  production.quantity` — equivalent to the old `item.quantity *
  production.quantity`, just sourced from the snapshot instead of the live
  recipe); if the raw material is batch/expiry-tracked,
  calls the existing `ProductVariationStockService::pickBatchesForSale()`
  **verbatim** (no new FEFO/FIFO logic) against that material's warehouse;
  decrements the raw material's `quantity` AND `reserved_quantity`
  (releasing exactly what was consumed) under lock; writes one
  `PRODUCTION_OUT`/`CONSUMPTION` stock transaction per batch drawn (or one
  aggregate row when not batch-tracked); writes a matching
  `production_consumptions` row per transaction; bumps the plan material's
  `consumed_quantity`. Then receives the finished good into **this
  production's own** warehouse/batch/expiry via `receiveOutput()`
  (weighted-average upsert identical to `GrnService`'s receipt formula +
  `ProductVariationStockService::upsertReceiptBatch()`, `PRODUCTION_IN`/
  `PRODUCTION` transaction type/reference; `expiry_date` falls back to
  `now()` when not set, matching the batch column's own DB default).
  Computes `material_cost`/`total_cost`/`unit_cost`, posts
  `ManufacturingAccountingService::postProductionCost()`, and calls
  `ManufacturingPlanService::recomputeProgress()`.
- `cancel($id, $reason)` — `draft`: nothing to reverse (no stock ever
  moved). `completed`: fetches every `product_variation_stock_transactions`
  row with `reference_id = production_id` and `reference_type IN
  (consumption, production)`, reverses them via the **same shared**
  `ProductVariationStockService::reverseStockTransactions()` +
  `recomputeLedger()` helpers GRN/Order reversal already use (never
  reinvents reversal logic), then re-reserves each consumed quantity back
  onto the plan material row + stock row (per
  `production_consumptions.warehouse_id`, which is already per-material-correct
  regardless of which warehouse each component came from), and recomputes
  plan progress — which, per the `recomputeProgress()` fix above, correctly
  drops the plan out of `COMPLETED` when this was the production that had
  completed it.

## `Manufacturing\ManufacturingAccountingService`

Copies `FixedAsset\FixedAssetAccountingService`'s exact shape
(`getSettings`/`assertProductionAccounts`/`resolveJournal`/`findExisting`/
`createEntry`) — same idempotent-on-`source_type`+`source_id` pattern, same
`JournalEntryService::assertBalanced()` + `AccountingPeriodService::assertPostable()`
guards every other posting module calls.

**Material-only production posts no journal entry at all.** Raw-material
value and finished-goods value both sit in the same
`AccountingSetting.default_inventory_account_id` control account — moving
cost from raw materials to finished goods within one GL account nets to zero,
identical to how a warehouse Transfer posts nothing today. Two journals exist
(seeded by migration `2026_09_04_090700_seed_manufacturing_journals`, short
codes `PCV`/`PWV`, mirroring `2026_09_02_221520_seed_fixed_asset_journals`):
- `postProductionCost()` — Dr Inventory / Cr `default_expense_account_id`,
  **only** when `labor_cost + overhead_cost + other_cost > 0`
  (`JournalSourceTypes::PRODUCTION_FINISHED_GOODS`, source_id = production_id).
- `postWastage()` — Dr `default_stock_adjustment_account_id` / Cr Inventory,
  intended for abnormal loss beyond a recipe's expected yield
  (`JournalSourceTypes::PRODUCTION_CONSUMPTION`, source_id = production_id).
  **Dead code, not currently called by anything** — since wastage% was
  removed from the recipe entirely, there's no "abnormal vs. expected"
  distinction left to post against. The method is kept, tested-ready, in
  case a future request reintroduces a wastage/loss concept.

## Reports

Manufacturing report controllers remain under `App\Http\Controllers\Admin\Reports`
(`ManufacturingPlanReportController`, `ProductionReportController`,
`MaterialConsumptionReportController`) plus `Inventory\RecipeBomReportController`.
They are enhanced as **master reports with modes** rather than duplicated pages:

- Production: `report_mode` = summary | performance | costing | variance |
  wastage_scrap (expected-vs-actual material proxy — no scrap table) | traceability
- Material Consumption: `group_by` + `report_mode=variance` (plan materials
  expected vs consumed)
- Recipe/BOM: `report_mode` = bom | cost_analysis | material_requirement | coverage

Sidebar: nested under **Inventory → Reports** (Consumption / Manufacturing /
Recipe-BOM). Routes stay in `module:manufacturing` group at
`admin/reports/{manufacturing-plan,production,material-consumption,recipe-bom-report}`.

`ReferenceResolverService` resolves production/consumption doc numbers **and**
admin edit URLs for drill-down from Stock Ledger and related reports.

`MaterialConsumptionReportService` applies `applyRoleScope()` inside
`whereHas('production')` because `production_consumptions` has no business/branch
columns of its own.

## Permissions & routes

Module keys `recipe`, `manufacturing-plan`, `production`, `manufacturing-reports`
(`PermissionRegistry.php` "Manufacturing modules" block). `recipe` is now only
`view`/`create`/`edit` (no `delete`/`status` — there's nothing to soft-delete
or archive on a single in-place-edited row). `manufacturing-plan` adds
`confirm`/`cancel` actions beyond CRUD. `production` is only
`view`/`create`/`edit`/`complete` ("Approve / Complete")/`cancel` ("Cancel /
Revert") — no `delete` (dead/unused) and no `start` (there is no multi-step
execution to start). Routes live under `Route::group(['middleware' =>
['module:manufacturing']], ...)` in `routes/web.php`, flat-listed (no
`Route::resource`): the Recipe group is just 4 routes (`GET admin/recipe` →
create page, `GET admin/recipe/for-variation/{product_variation_id}`,
`POST admin/recipe/item` and `DELETE admin/recipe/item/{product_recipe_item_id}`
for the per-line AJAX add/remove) since there is no list/show page and no
whole-form submit. `SubscriptionModuleRegistry` registers `manufacturing` (umbrella,
`type=feature`) + the three children (`parent=manufacturing`), with a
`backfill_package_modules_for_manufacturing` migration so existing packages
default to enabled (mirrors the Fixed Asset backfill).

## API / storefront exposure

None needed. Once `ProductionService::complete()`/`receiveOutput()` writes a
normal `production_in` transaction into `ProductVariationStock`, the finished
product/variation automatically surfaces through the existing
`ProductService::getWebsiteListing()`/`getWebsiteDetail()` used by both
`/api/v1/products` and `/api/mobile/products` — no Manufacturing-specific API
code exists or is needed. Recipe/Plan/Production management is admin-only
Blade UI, consistent with Fixed Assets/HRM.

## Known simplifications (see code comments for exact locations)

- `ManufacturingAccountingService::postWastage()` exists but nothing calls it
  (see above) — kept only as a ready-to-wire method, not active code.
- A `draft` Production left orphaned by its parent Plan being cancelled is
  not auto-cancelled — it becomes inert (its plan is no longer
  `NOT_COMPLETE`, so `save()`/`complete()` reject it) but isn't cleaned up
  automatically.
