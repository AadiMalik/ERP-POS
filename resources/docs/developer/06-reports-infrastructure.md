# Reports Infrastructure

~90 report classes share one shape. This documentation portal's own PDF export
(see [The Documentation System Itself](12-documentation-system.md)) follows the
same `Pdf::loadView(...)->setPaper(...)->stream(...)` pattern described here.

## The Uniform Action Set

Every report controller under `App\Http\Controllers\Admin\Reports\**` implements:
`index` (renders the filter/table screen), `data` (server-side DataTable feed,
`POST`), `print` (browser `window.print()` view), `pdf`, `export` (Excel), and
`export-csv`. Three exceptions render as computed statements instead of a DataTable
(no `data()` action): Profit & Loss, Balance Sheet, and Cash Flow Statement.

### Cash Flow Statement

Direct-method statement under `module:accounting`:

- Controller: `CashFlowReportController`
- Service: `CashFlowReportService::build()`
- Classification: `AccountClassifier::cashFlowBucket()` (counterparty account
  type/sub-type codes → Operating / Investing / Financing)
- Cash universe: `AccountClassifier::isCashOrBank()` (Cash & Cash Equivalents
  sub-type + `accounting_settings` default cash/bank) — never hard-coded IDs
- Ledger engine: `AccountingLedgerQueryService` for opening/closing balances;
  period movements attributed per journal entry so cash↔cash transfers are
  excluded and cash is never double-counted
- Permissions: `reports.cash-flow.{view,print,pdf,export,export-csv}`
- Route prefix: `/admin/reports/cash-flow`

## PDF Generation Pattern

```php
use Barryvdh\DomPDF\Facade\Pdf;

public function pdf(Request $request)
{
    $rows = $this->service->build($request->all());
    $business_id = $request->business_id ?? Auth::user()->business_id;
    $this->log($business_id, 'pdf'); // audit trail, see below

    $print_config = $this->print_setting_resolver->resolve($business_id);

    return Pdf::loadView('admin.reports.xxx.pdf', compact('rows', 'request'))
        ->setPaper($print_config->page('paper_size', 'a4'), $print_config->page('orientation', 'portrait'))
        ->stream('xxx-report.pdf');
}
```
- Always `stream()` (open in browser), never `download()`, for on-demand reports.
- Paper size/orientation come from the **business's own Print Settings** via
  `PrintSettingResolverService::resolve($business_id)` — never hardcoded — so each
  tenant can configure their own default paper size.
- The PDF Blade is a **standalone HTML document**, not `@extends('layouts.app')` —
  dompdf renders it in isolation. Use `font-family: 'DejaVu Sans', sans-serif`
  (dompdf's font support is limited) and table-based layout for anything dompdf
  needs to lay out precisely (dompdf doesn't support flexbox).
- Reports needing a company letterhead include the shared partial
  `resources/views/admin/partials/print/pdf_header.blade.php`, driven by
  `App\Support\Print\PrintConfig` (`orderedHeaderFields()`, `isVisible()`,
  `fieldStyle()`) so each business can customize which header fields show.

`resources/views/layouts/print.blade.php` is a **separate** layout for the
browser-`print()` action (has a Print/Close toolbar, skippable via `?auto=1` for
silent POS receipt printing) — it is not used by the dompdf PDF views.

## Audit Trail

Every report action (`print`, `pdf`, `export`, `export-csv`) calls a small
`protected function log()` wrapping `DocumentSendLogService::log(...)` in a
try/catch (a failure only logs a warning, never blocks the response) — this is how
"who exported what, when" stays traceable.

## Excel Export Pattern

```php
class XxxReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Collection $rows) {}
    public function collection() { return $this->rows; }
    public function headings(): array { return [...]; }
    public function map($row): array { return [...]; }
}
```
Controller usage:
```php
Excel::download(new XxxReportExport($rows), 'xxx-report.xlsx');
Excel::download(new XxxReportExport($rows), 'xxx-report.csv', \Maatwebsite\Excel\Excel::CSV); // same class, third arg
```

## Adding a New Report

1. Add a Service under `app/Services/Concrete/Admin/Reports/**` that builds the
   row collection.
2. Add a Controller with the six standard actions.
3. Add the report's permissions to `PermissionRegistry` (one `view` plus one per
   action it supports — see the `reports.*` naming convention, e.g.
   `reports.service-sale-report.pdf`).
4. Add the route to the report-controller loop/group in `routes/web.php`.
5. Add views: `index.blade.php`, `pdf.blade.php`, `print/print.blade.php`.
6. Add an `App\Exports\XxxReportExport` class if Excel/CSV export is needed.

## Inventory Reporting System

Inventory reports live under `App\Http\Controllers\Admin\Reports\Inventory\`
(plus the pre-existing `StockLedgerReportController` and manufacturing report
controllers). Stock-facing routes are gated `module:inventory`; manufacturing
consumption/production/recipe report routes remain `module:manufacturing`, but
the **sidebar nests all four report groups under Inventory → Reports** so
manufacturing is reported as inventory flow.

### Master reports (modes avoid duplicate pages)

| Master report | Covers | Primary source |
|---------------|--------|----------------|
| Stock Summary | Summary, availability, warehouse/branch, low stock/reorder | `product_variation_stocks` |
| Stock Ledger | Ledger, product ledger, movement filters | `product_variation_stock_transactions` |
| Stock Valuation | Valuation at avg cost | `product_variation_stocks.avg_price` |
| Stock Aging | Aging buckets + slow/fast/non-moving | stocks + last txn date |
| Stock Transfer | Transfer notes | `transfer_notes` |
| Stock Reconciliation | Stock taking + adjustment movements | `stock_taking_details` / ledger |
| Stock Loss | Damage/wastage/expired txns | ledger `transaction_type` |
| Batch/Expiry | Batch stock + near/expired | `product_variation_batches` |
| Material Consumption | Detail, group-bys, expected vs actual | `production_consumptions` / plan materials |
| Production | Summary, yield, costing, variance, wastage proxy, traceability | `productions` |
| Manufacturing Plan | Plan progress | `manufacturing_plans` |
| Recipe/BOM | BOM, cost, material requirement, coverage | `product_recipes` + items |

Shared base: `BaseInventoryReportController`, `InventoryReportExport`,
`AppliesInventoryReportScope`. Drill-down uses `ReferenceResolverService::resolveUrl()`
and links into ledger / production / plan screens.

**Known limits:** no recipe versioning (one recipe per variation); no scrap/rework
tables — production wastage is expected-vs-actual material proxy; damage/wastage
note CRUD does not exist (loss report reads ledger enums only).

## Orders / POS Reports

Order-side reports live under `App\Http\Controllers\Admin\Reports\Orders\` and
`App\Services\Concrete\Admin\Reports\Orders\`, sharing:

- `BaseOrderReportController` — same six-action plumbing as HRM base report
  controllers (permission middleware, print/pdf/export/export-csv, audit log).
- `BaseOrderReportService` — shared `applyCommonFilters()` (business / branch /
  sale_date range / order source / status / customer / product / variation),
  `dueOf()`, `paymentStatusOf()`, and `filterByPaymentStatus()`. Date filtering
  uses `orders.sale_date` by default (override the `date` column when joining).

Routes are registered under `module:pos` next to customer reports in
`routes/web.php`. Sidebar entries sit under **Orders → Reports**. Permissions
use slugs such as `reports.product-sales.*`, `reports.branch-sales.*`,
`reports.offline-orders-report.*`.

Current set: Order Detail, Product Sales, Variation Sales, Customer Sales,
Branch Sales, Order Source Sales, Payment Method Sales, Order Status, Cancelled
Orders, Due/Credit Sales, Discount Report, Order Tax Report, Top Selling,
Offline Orders. Order Correction Report sits alongside these under the same
Orders → Reports menu and permission-slug convention, but reads `activity_logs`
rows instead of `orders` — see `03-modules-controllers-services.md`.

There is no Category-wise Sales report, no dedicated Returned/Refunded Orders
report (Sales Returns is a full transactional module — see
`OrderReturnService` — but has no aggregate report of its own yet), and no
Profit/Margin report at product or variation level (`order_details.cost_price`
is captured but not currently surfaced by any report; `ProfitLossReportService`
is a GL-account-based financial statement, not a per-product margin report).
