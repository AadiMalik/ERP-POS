# Waste / Damage / Expiry

An independent inventory-loss transaction (Waste, Damaged, Expired, Spoiled,
Broken, Lost/Missing, Other) with a mandatory approval step before stock
actually moves. Deliberately has **no** dependency on Production/Manufacturing
— loss can occur at any stage (storage, display, handling, transport, a
branch) and the module never assumes a manufacturing source. Built entirely
on pre-existing stock/batch/ledger/accounting machinery, mirroring
**Stock Taking**'s pending → approve/cancel workflow.

## The enums were already reserved

Before this module existed, `App\Enums\TransactionType` already had
`DAMAGE`/`EXPIRED`/`WASTAGE` and `App\Enums\ReferenceType` already had
`DAMAGE_NOTE`/`EXPIRY_NOTE`/`WASTAGE_NOTE` — both DB-level enum columns
(`product_variation_stock_transactions.transaction_type`/`reference_type`)
included these values from their very first migration. `StockLossReportController`
+ `StockLossReportService` (under Reports → Inventory) also already existed,
filtering the stock ledger by exactly those 3 transaction types — its own
doc-comment said *"dedicated damage/wastage/expiry note CRUD does not exist;
data only appears when such transaction_types were posted to the stock
ledger."* This module is that missing CRUD — same pattern as Manufacturing's
pre-reserved `PRODUCTION_IN`/`PRODUCTION_OUT`.

`App\Enums\LossType` is the **new** 7-value enum
(waste/damaged/expired/spoiled/broken/lost/other) that this module's forms
and report actually use; it maps each value onto one of the 3 pre-existing
ledger buckets so `StockLossReportService` needed zero code changes:

| LossType | TransactionType | ReferenceType |
|---|---|---|
| `damaged`, `broken` | `DAMAGE` | `DAMAGE_NOTE` |
| `expired`, `spoiled` | `EXPIRED` | `EXPIRY_NOTE` |
| `waste`, `lost`, `other` | `WASTAGE` | `WASTAGE_NOTE` |

`LossType::toTransactionType()`/`toReferenceType()` are the single mapping
point — see `app/Enums/LossType.php`.

## Schema

Migrations `2026_09_04_140000`–`2026_09_04_140400`:

| Table | Purpose |
|---|---|
| `loss_reasons` | Configurable per-business reasons (`business_id`, `name`, `status`), plain lookup CRUD — same shape as `expense_categories` minus the accounting link. |
| `waste_damage_expiries` | Header: `reference_no` (`WDE-0001`, `generateWasteDamageExpiryNo()`), `business_id`/`branch_id`/`warehouse_id`, `transaction_date`, `reference` (free-text, optional link to a known source transaction), `notes`, `total_quantity`/`total_value`, `status` (`pending`/`approved`/`cancelled`), `approvedby_id`/`date_approved`, full audit set. |
| `waste_damage_expiry_details` (child) | One row per product/variation write-off: `product_variation_batch_id` (nullable — batch selection is optional per the business requirement), `batch_no`/`expiry_date` (snapshotted independently of the batch link, since expiry can be recorded even for a non-batch-tracked line), `quantity`, `unit_cost`/`value` (re-snapshotted at approval time against live cost, draft-time values are only an estimate), `loss_type`, `loss_reason_id` (nullable FK), `notes`. |

`journal_entries.source_type` is a DB-level ENUM; migration
`2026_09_04_140400_add_stock_loss_to_journal_entries_source_type.php` adds
`JournalSourceTypes::STOCK_LOSS = 'Stock Loss'` to it (same pattern as
`2026_09_04_120100_simplify_manufacturing_status_enums.php`'s raw `ALTER
TABLE MODIFY COLUMN`). Migration `2026_09_04_140300_seed_stock_loss_journal.php`
seeds one `Journal` row, short code `SLV` ("Stock Loss Voucher") — mirrors
`2026_09_04_090700_seed_manufacturing_journals.php`.

## `WasteDamageExpiryService`

- `save($obj)` — create/update a `pending` header + replace its detail lines
  (blocks editing once not `pending`, same guard as
  `StockTakingService::save()`). Per line: validates `quantity` against
  **live** `ProductVariationStock.quantity - reserved_quantity` (respecting
  Manufacturing's `reserved_quantity`, same rule `OrderService`/
  `TransferNoteService` already enforce) and, if a batch is selected,
  against that `ProductVariationBatch.quantity` — both re-validated again at
  approval time since stock can move between draft and approval.
- `status($obj)` — the only status machine allowed is `pending → approved`,
  `pending → cancelled`, `approved → cancelled`; a `cancelled` record can
  never transition again. `approved → pending` is deliberately not a valid
  transition — corrections go through cancel + a fresh record, per the
  business requirement that approved transactions aren't directly editable.
- `applyPosting()` — inside `DB::transaction()`, locks each `ProductVariationStock`
  (and `ProductVariationBatch`, if any) row, re-validates availability, then
  for each line: creates one `ProductVariationStockTransaction`
  (type/reference from the `LossType` mapping table above,
  `reference_id` = the header id, `quantity_after` computed from the
  **locked live quantity**) and decrements that same locked
  `ProductVariationStock.quantity` directly — the same pattern
  `StockTakingService::applyStockTakingPosting()` uses for a shortage, and
  deliberately **not** `ProductVariationStockService::recomputeLedger()`:
  that method replays a product/variation/warehouse's full transaction
  history from scratch, so calling it on the forward-posting path would
  silently drop any balance that didn't itself arrive via a stock
  transaction (an imported/seeded opening balance, for instance) instead of
  just subtracting the write-off from whatever is actually on hand right
  now. `recomputeLedger()` is still exactly right for **reversal** (see
  below), since at that point the goal genuinely is "recompute from what
  remains after removing these transactions". If batched, also calls the
  existing `ProductVariationStockService::adjustBatchQuantity($batch_id,
  -$qty)`. Then, only if the total value is non-zero: asserts accounting is
  enabled and both
  `AccountingSetting.default_inventory_account_id` /
  `default_stock_adjustment_account_id` are configured (else **blocks
  approval** with an error — same behavior `StockTakingService` already has
  for a Stock Taking shortage/gain), asserts the accounting period is
  postable, and posts one balanced JV — Dr Stock Adjustment / Cr Inventory —
  under `source_type = JournalSourceTypes::STOCK_LOSS`. Deliberately reuses
  the *same* inventory/stock-adjustment accounts Stock Taking posts against
  (no new per-loss-type accounts) — a business decision to keep accounting
  configuration in one place rather than proliferate settings.
- `reversePosting()` — soft-deletes the JE (if any) and hands every stock
  transaction referencing the header straight to the existing
  `ProductVariationStockService::reverseStockTransactions()`, which already
  reverses the batch delta (sign-aware via `TransactionType::isInbound()`)
  and recomputes the ledger. **No new reversal logic was written** — this is
  the same call `StockTakingService::reverseStockTakingPosting()` makes.
- `delete()` — `pending` deletes directly; `approved` reverses first, then
  soft-deletes+cancels (same combined behavior as `StockTakingService::delete()`).

Both `applyPosting()`/`reversePosting()` are idempotent (guarded by
`ProductVariationStockTransaction::where('reference_id', ...)->exists()` /
`JournalEntry::where('source_type', ...)->exists()`), matching every other
approve/reverse pair in the codebase.

## Reports

`WasteDamageExpiryReportService`/`WasteDamageExpiryReportController`
(`App\...\Reports\Inventory`, extends `BaseInventoryReportController` same as
`StockLossReportController`) reads **directly from `waste_damage_expiries`/
`details`**, not the stock ledger — unlike `StockLossReportService`, which
only ever sees already-approved postings, this report also surfaces
pending/cancelled records and the full batch/reason/approval trail. Filters:
date range, business/branch/warehouse, product/variation, batch/lot, expiry
date, loss type, reason, status; role-scoped via the shared
`AppliesInventoryReportScope` trait + `applyRoleScope()`. This is additive —
`StockLossReportService` is unchanged and keeps serving as the ledger-level
cross-check (it will show real rows once approvals start posting).

`ReferenceResolverService::resolveDocNo()`/`resolveUrl()` gained
`DAMAGE_NOTE`/`EXPIRY_NOTE`/`WASTAGE_NOTE` cases resolving to
`WasteDamageExpiry.reference_no` / its edit route, so Stock Ledger/Product
Ledger drill-down works immediately, same pattern as the existing
`STOCK_TAKING` case.

Near-expiry/expired stock visibility needed **no new report** — the
pre-existing `BatchExpiryReportController` ("Batch/Lot & Expiry" report)
already provides that view; this module only adds the write-off action once
a business decides to act on it.

## Permissions & routes

Module keys `waste-damage-expiry` (`view`/`create`/`edit`/`delete`/`approve`/
`cancel`/`print` — `approve` is the dedicated, configurable permission the
business requirement asked for) and `loss-reason` (`view`/`create`/`edit`/
`delete`), both in `PermissionRegistry`'s Inventory block, plus a
`reports.waste-damage-expiry` report block. Routes are flat-listed (no
`Route::resource` extras beyond the standard CRUD) inside the existing
`Route::group(['middleware' => ['module:inventory']], ...)` group in
`routes/web.php` — no new subscription-module-gating key was needed since
this isn't a separately-priced package feature, it's part of core Inventory
like Stock Taking.

Role defaults: Business Admin and Branch Admin get full access automatically
(`businessNames()` / the `businessNames() - role/setting` diff already
include every non-system permission); Inventory Manager's explicit module
list in `RoleDefaultPermissions` was extended with both keys plus the new
`reports.waste-damage-expiry.*` permissions; `operationalModuleKeys()` was
extended with both keys so General Manager's broad default also covers them
(minus `delete`, same as every other module in that list).
