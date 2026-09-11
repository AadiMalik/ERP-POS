# Cost Price Adjustment

A controlled, approval-gated **valuation-only** correction to a product
variation's current cost in a specific warehouse — never a quantity movement.
Distinct from **Stock Taking** (corrects quantity, values the difference at
the *current* cost) and **Waste / Damage / Expiry** (removes quantity, valued
at the *current* cost): Cost Price Adjustment is the only transaction that
changes the cost itself while leaving quantity untouched. Built entirely on
the pre-existing weighted-average stock/batch/ledger/accounting machinery,
mirroring **Stock Taking**'s pending → approve/cancel workflow and
**Waste/Damage/Expiry**'s CRUD/permission shape.

## Why this exists — the three cost surfaces

Before this module, "cost" meant three different things depending on where
you looked:

- `ProductVariation.purchase_price` — a **static**, manually-entered default,
  set only via the Product form. Never updated by a purchase. Only ever read
  as a fallback when no stock row exists yet (`RecipeBomReportService`) or as
  a suggested price on a Purchase Request.
- `ProductVariationStock.avg_price` — the **live**, per-warehouse
  weighted-average cost. This is what almost every inventory report (Stock
  Valuation, Stock Summary, Recipe/BOM) actually treats as "current cost".
- `ProductVariationBatch.avg_price` — the same weighted-average, further
  scoped to a batch, for batch/expiry-tracked variations.

Cost Price Adjustment targets the second and third of these — it revalues
live stock (and every active batch, if the variation is batch-tracked) to a
new unit cost. It never touches `ProductVariation.purchase_price` (the static
default) or any historical purchase transaction.

## A new transaction type, deliberately classified as inbound

`App\Enums\TransactionType::COST_ADJUSTMENT = 'cost_price_adjustment'` and
`App\Enums\ReferenceType::COST_PRICE_ADJUSTMENT = 'cost_price_adjustment'` are
new (migration `2026_09_11_100002_add_cost_price_adjustment_to_stock_transaction_enums.php`
widens the DB-level `ENUM` columns on `product_variation_stock_transactions`,
same raw `ALTER TABLE MODIFY` pattern as every other new transaction type in
this codebase).

`COST_ADJUSTMENT` is included in `TransactionType::inboundTypes()` even
though it never moves quantity (`base_quantity` is always `0`). This is
required, not cosmetic: `ProductVariationStockService::recomputeLedger()`
replays inbound transactions as
`avg_price = ((quantity * avg_price) + total_price) / new_quantity`. With
`base_quantity = 0` (so `new_quantity = quantity`, no movement) and
`total_price` carrying the adjustment's signed value delta, this reduces
exactly to `avg_price + total_price / quantity = new_cost`. Classifying it as
outbound instead would make a later full-ledger replay (triggered by an
unrelated reversal on the same SKU/warehouse) silently drop the adjustment's
effect on `avg_price` — a real, quietly-corrupting bug this classification
avoids.

## Schema

Migrations `2026_09_11_100000`–`2026_09_11_100006`:

| Table | Purpose |
|---|---|
| `cost_price_adjustments` | Header/flat row — one adjustment = one `product_variation_id` + `warehouse_id`. `reference_no` (`CPA-0001`, `generateCostPriceAdjustmentNo()`), `previous_cost_price` (captured from live `avg_price` at save, re-verified — not trusted — at approval), `new_cost_price`, `quantity_on_hand`/`difference_per_unit`/`total_adjustment_amount` (all persisted with the values *actually applied* at approval, not the draft-time estimate), `reason`, `notes`, `reference`, `status` (`pending`/`approved`/`cancelled`), `approvedby_id`/`date_approved`, full audit set. |
| `cost_price_adjustment_batches` (child) | Only populated when the target variation is batch/expiry-tracked. One row per batch revalued: `quantity`, `previous_batch_avg_price`/`new_batch_avg_price` (captured so cancellation can restore the *exact* prior batch cost, not just the warehouse average), `batch_adjustment_amount`. |

Why a flat header instead of a header/detail pair like Waste/Damage/Expiry:
the required fields (Previous Cost, New Cost, Difference, Total, Approved
By/Status) describe one reviewable fact for one SKU, not repeating line
items — a bulk write-off run naturally spans many SKUs, a deliberate cost
correction does not.

`journal_entries.source_type` is a DB-level ENUM; migration
`2026_09_11_100003_add_inventory_cost_adjustment_to_journal_entries_source_type.php`
adds `JournalSourceTypes::INVENTORY_COST_ADJUSTMENT = 'Inventory Cost Adjustment'`
to it (same pattern as `2026_09_04_140400_add_stock_loss_to_journal_entries_source_type.php`).
Migration `2026_09_11_100005_seed_inventory_cost_adjustment_journal.php` seeds
one `Journal` row, short code `ICJ` ("Inventory Cost Adjustment Voucher") —
mirrors `2026_09_04_140300_seed_stock_loss_journal.php`.

## Accounting Settings / COA

A new, **dedicated** `AccountingSetting.default_inventory_cost_adjustment_account_id`
field (migration `2026_09_11_100004_...`) — deliberately *not* a reuse of
`default_stock_adjustment_account_id` (which Waste/Damage/Expiry and Stock
Taking use for physical-loss/count-variance postings), so a pure valuation
correction never mixes with quantity shrinkage in the P&L. Follows the exact
`default_*_account_id` convention: added to `AccountingSetting::$fillable`,
`ChartOfAccountsTemplateSeeder` (new child account `520004-002` "Inventory
Cost Adjustment / Revaluation" under the existing `520004` "Stock Adjustment"
parent), `AccountingSettingCloneService::ACCOUNT_FIELDS` (so
`AccountingSetupWizardService::setupForBusiness()` auto-provisions it on
every new business, idempotently, with zero other code changes), and exposed
in **Settings → Accounting**. Migration `2026_09_11_100006_backfill_...`
re-runs the template seeder + both clone services for every *existing*
business, mirroring `2026_09_03_150800_backfill_loyalty_coa_for_existing_businesses.php`.

## `CostPriceAdjustmentService`

- `save($obj)` — create/update a `pending` record only (blocks editing once
  not `pending`). Resolves `business_id`/`branch_id` from the selected
  `Warehouse` (never trusted from request input). `previous_cost_price`,
  `quantity_on_hand`, `difference_per_unit`, `total_adjustment_amount` are
  always **recomputed from live `ProductVariationStock`** here — never
  accepted from the request, so `previous_cost_price` can never be manually
  edited — but these are still only a draft preview; the authoritative
  figures are recomputed again under a row lock at approval. Rejects a second
  `pending` adjustment for the same variation+warehouse (duplicate-in-flight
  guard) and requires `new_cost_price > 0`.
- `status($obj)` — `pending → approved|cancelled`, `approved → cancelled`
  (same state machine every approval-workflow module uses); dispatches
  `applyPosting()`/`reversePosting()`.
- `applyPosting()` — inside `DB::transaction()`: locks the `ProductVariationStock`
  row, **blocks approval if quantity is zero** (weighted-average is undefined
  at zero stock, and a cost posted there would silently vanish on the next
  unrelated `recomputeLedger()` replay — `ProductVariation.purchase_price`
  is the correct mechanism for a cost with no stock yet), and re-verifies
  `previous_cost_price` against the *live* `avg_price` — if it has drifted
  since the draft was saved, approval is blocked with an error rather than
  silently posting against a stale baseline. If the variation has active
  batches in that warehouse: revalues **every** batch to the new cost and
  computes `total_adjustment_amount` as the **exact sum of each batch's own
  delta** (`batch.quantity * (new_cost - batch.avg_price)`), not a flat
  `total_qty * (new_cost - warehouse_avg)` — this stays correct even when
  batches currently carry divergent costs, and since every batch ends up at
  the same `new_cost`, the batch-weighted average trivially equals it, so
  `ProductVariationStock.avg_price` is set to `new_cost` directly and stays
  consistent with Batch Expiry/Stock Aging. Writes one
  `ProductVariationStockTransaction` per batch (or one warehouse-level row if
  no batches), always `base_quantity = 0` — see the classification note
  above. Then, only if the total is non-zero: asserts accounting is enabled
  and both `default_inventory_account_id` /
  `default_inventory_cost_adjustment_account_id` are configured (else
  **blocks approval**), asserts the accounting period is postable, and posts
  one balanced JV: increase → Dr Inventory / Cr Inventory Cost Adjustment;
  decrease → reversed. Idempotent (guarded by the same
  `ProductVariationStockTransaction::where('reference_id', ...)->exists()` /
  `JournalEntry::where('source_type', ...)->exists()` pattern every other
  approve/reverse pair uses).
- `reversePosting()` — soft-deletes the JE (if any) and hands the stock
  transactions to the existing `ProductVariationStockService::reverseStockTransactions()`,
  which restores `ProductVariationStock.avg_price` via `recomputeLedger()` —
  **no new warehouse-level reversal logic was written**. One genuinely new
  mechanic, because nothing else in the codebase needs it:
  `reverseStockTransactions()` never touches `ProductVariationBatch.avg_price`,
  so for the batch-tracked case `reversePosting()` explicitly restores each
  batch's `avg_price` from `CostPriceAdjustmentBatch.previous_batch_avg_price`
  (guarded: refuses to cancel if a later receipt/adjustment has touched that
  batch since, rather than silently overwriting it).
- `delete()` — `pending` deletes directly; `approved` reverses first, then
  soft-deletes+cancels (same combined behavior as every other module).

## Reports

`CostPriceAdjustmentReportService`/`CostPriceAdjustmentReportController`
(`App\...\Reports\Inventory`, extends `BaseInventoryReportController` same as
`StockValuationReportController`) reads directly from `cost_price_adjustments`
(with a single extra query resolving every row's posted JV by
`source_type`/`source_id`, avoiding N+1), not the stock ledger — so it
surfaces pending/cancelled records too, not just posted ones. Filters: date
range, business/branch/warehouse, product/variation, created-by user, status;
totals for increase/decrease/net adjustment; role-scoped via the shared
`AppliesInventoryReportScope` trait.

`StockLedgerReportService::build()` needed one targeted fix: its `value = qty
* unit_price` formula reads `base_quantity`, which is always `0` for this
transaction type, so it's special-cased to `value = total_price` instead —
otherwise a real value move would silently render as `0` in the ledger.
`direction`/`quantity_in`/`quantity_out` need no change: they naturally
resolve to `in`/`0`/`0`, which is exactly the "distinguishable from a
quantity movement" rendering required.

`ReferenceResolverService::resolveDocNo()`/`resolveUrl()` gained a
`COST_PRICE_ADJUSTMENT` case resolving to `CostPriceAdjustment.reference_no` /
its edit route, so Stock Ledger drill-down works immediately.

**No change needed** in Stock Valuation, Stock Summary, or Current Stock —
they read live `avg_price` directly, so they reconcile automatically once a
JV posts. **No change needed** in `StockReconciliationReportService` — it
intentionally filters to quantity-reconciliation transaction types only, and
`COST_ADJUSTMENT` is correctly excluded since it isn't one. **No change
needed** in COGS/Profit & Loss — they source from posted Journal Entries, not
`avg_price`, so they reconcile automatically too. **No change needed** in
sale-margin estimation (`OrderDetail.cost_price`) — it's a frozen snapshot
taken at sale time; a sale made after an adjustment naturally snapshots the
corrected cost, a historical sale keeps its original snapshot (never
rewritten).

## Permissions & routes

Module key `cost-price-adjustment` (`view`/`create`/`edit`/`delete`/
`approve`/`cancel`/`print`, in `PermissionRegistry`'s Inventory block) plus a
`reports.cost-price-adjustment` report block. Routes are flat-listed inside
the existing `Route::group(['middleware' => ['module:inventory']], ...)`
group in `routes/web.php` — no new subscription-module-gating key, it's core
Inventory like Stock Taking and Waste/Damage/Expiry.

Role defaults: Business Admin/Branch Admin get full access automatically
(`businessNames()`); Inventory Manager's explicit module list in
`RoleDefaultPermissions` was extended with `cost-price-adjustment` plus the
new `reports.cost-price-adjustment.*` permissions; `operationalModuleKeys()`
was extended so General Manager's broad default also covers it (minus
`delete`, same as every other module in that list).

## UI

- Index: `resources/views/admin/cost_price_adjustment/index.blade.php` —
  filters (business / warehouse / status / date), DataTable, status dropdown
  (approve confirms with SweetAlert before posting), print, pending-only
  edit, delete (approved delete reverses first).
- Create/edit: `resources/views/admin/cost_price_adjustment/create.blade.php`
  — warehouse + product + variation (locked on edit), live Previous Cost /
  Quantity On Hand from `GET …/stock/{warehouse}/{variation}`, optional
  read-only batch breakdown from `GET …/batches/{warehouse}/{variation}`,
  Difference/Unit and Total computed in the browser as the user types the
  new cost (server recomputes from live stock on save and again at
  approval). Approval is blocked if quantity is zero.
- Print: `resources/views/admin/cost_price_adjustment/print/print.blade.php`.
- Report: `resources/views/admin/reports/inventory/cost_price_adjustment/`
  (`index`, `pdf`, `print/print`) — same filter/export shape as other
  inventory reports.
- Sidebar: Inventory → Cost Price Adjustment (`cost-price-adjustment.view`)
  and Inventory → Reports → Cost Price Adjustment
  (`reports.cost-price-adjustment.view`).
- Settings → Accounting exposes **Inventory Cost Adjustment Account**.

User-facing strings live in `lang/*/cost_price_adjustment.php` plus the
shared keys `settings.coa_inventory_cost_adjustment_account`,
`sidebar.cost_price_adjustment` / `sidebar.cost_price_adjustment_report`,
and `reports.cost_price_adjustment` / `positive_adjustment_total` /
`negative_adjustment_total` / `net_adjustment_total`.

## Routes

All inside `Route::group(['middleware' => ['module:inventory']], …)` in
`routes/web.php`:

| Method | URI | Action | Permission |
|---|---|---|---|
| resource | `admin/cost-price-adjustment` | index/create/store/edit | `cost-price-adjustment.view` / `.create` / `.create\|.edit` / `.edit` |
| POST | `admin/cost-price-adjustment/data` | DataTable | `.view` |
| POST | `admin/cost-price-adjustment/change-status` | approve/cancel | `.approve\|.cancel` |
| GET | `admin/cost-price-adjustment/details/{id}` | JSON detail | `.view` |
| GET | `admin/cost-price-adjustment/stock/{warehouse}/{variation}` | live cost/qty | `.view` |
| GET | `admin/cost-price-adjustment/batches/{warehouse}/{variation}` | live batches | `.view` |
| GET | `admin/cost-price-adjustment/{id}/print` | print | `.print` |
| resource destroy | `admin/cost-price-adjustment/{id}` | delete | `.delete` |
| GET/POST | `admin/reports/cost-price-adjustment` (+ print/pdf/export/export-csv) | report | `reports.cost-price-adjustment.*` |
