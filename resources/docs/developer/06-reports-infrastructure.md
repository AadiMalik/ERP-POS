# Reports Infrastructure

~90 report classes share one shape. This documentation portal's own PDF export
(see [The Documentation System Itself](12-documentation-system.md)) follows the
same `Pdf::loadView(...)->setPaper(...)->stream(...)` pattern described here.

## The Uniform Action Set

Every report controller under `App\Http\Controllers\Admin\Reports\**` implements:
`index` (renders the filter/table screen), `data` (server-side DataTable feed,
`POST`), `print` (browser `window.print()` view), `pdf`, `export` (Excel), and
`export-csv`. Two exceptions render as computed statements instead of a DataTable
(no `data()` action): Profit & Loss and Balance Sheet.

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
