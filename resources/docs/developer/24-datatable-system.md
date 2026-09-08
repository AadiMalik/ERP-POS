# Centralized DataTable System

Reusable DataTable behaviour for ERP list screens.

**Engine mode (Customers / Suppliers):** a module defines query, columns,
filters, and listing chrome. `<x-erp-data-table key="foo" />` renders the
page. Searching, sorting, pagination, Customize Table, preferences, and
export-column selection live in one engine.

**Attach mode (every other DataTable):** `public/assets/js/admin/erp-datatable.js`
listens for DataTables `init.dt` and adds **Customize Table** to any listing
table that has an id — including Yajra lists that still use
`admin.partials.datatable` and non-engine screens such as POS Order History.
Preferences are stored under the HTML table id (`order_history_table`,
`warehouse_table`, …). Skip a table with `data-erp-skip-customize`.

## Where it lives

| Piece | Path |
|---|---|
| Registry | `app/Support/DataTables/DataTableRegistry.php` |
| Contract + base class | `Contracts/DataTableDefinition.php`, `AbstractDataTable.php` |
| Engine / filters / permissions | `DataTableEngine.php`, `DataTableFilterEngine.php`, `DataTableAuthorizer.php` |
| Preferences / export | `DataTablePreferenceService.php`, `DataTableExportService.php` |
| Facade | `DataTableManager.php` |
| HTTP | `App\Http\Controllers\Admin\DataTableController` |
| Module definitions | `app/DataTables/{Customer,Supplier}DataTable.php` |
| Shared listing component | `app/View/Components/ErpDataTable.php`, `resources/views/components/erp-data-table.blade.php` |
| JS / CSS | `public/assets/js/admin/erp-datatable.js`, `public/assets/css/erp-datatable.css` |
| Config | `config/erp_datatables.php` |

## Adding a new ERP DataTable (engine)

1. Create `app/DataTables/FooDataTable.php` extending `AbstractDataTable`
   (`key()`, `label()`, `permission()`, `query()`, `columns()`, optional
   `filters()`, `listing()`, `moduleKey()`).
2. Register the key in `DataTableRegistry::definitions()`.
3. Index view is only:

```blade
@extends('layouts.app')
@section('content')
    <x-erp-data-table key="foo" />
@endsection
```

Do **not** copy Yajra `getData()` or per-page DataTable JS. Default `visible`
columns must match the columns that screen already showed.

Unmigrated lists keep `admin.partials.datatable` — Customize still appears
via attach mode. No per-CRUD JS is required.

## UI

- **Customize Table** is an icon-only button on the **same row as page
  numbers** (right side). When pagination is at the top, that row sits above
  the page-length control; when it is at the bottom, the icon sits beside
  the bottom pager. Engine tables also support column order. Attach mode
  does not reorder columns (Yajra column indexes would break).
- **Export checkboxes** in Customize appear only when that card has an
  **Export** button (`.import-export-export-btn`). Order History has no
  Export, so it has no export ticks. Customers/Suppliers use
  `listingCanExport()` (`import_export_module` + permission, optional
  `has_export => false`).
- Filters on engine pages use the same listing layout as other CRUD screens
  (`#filterSection`, `admin.partials.date_filter`, `#search_btn`,
  `#reset_filter`).
- **Pagination position** (page numbers) comes from Business Settings
  (`business_settings.datatable_pagination_position`): bottom (default), top,
  or both. When top/both, page numbers sit **above** the page-length control
  (same side as "Show N entries"), not between the length/search row and the
  table. `window.erpDtLayout()` in `layouts/js.blade.php` applies it to every
  DataTable (engine, Yajra partial, and the global DataTables defaults).

## Preferences

`datatable_preferences` stores one row per `user_id` + `table_key`. Engine
keys are registry keys (`customers`). Attach keys are HTML table ids.

## Permissions

- Opening / querying an engine table: definition `permission()` (e.g. `customer.view`)
- Export (top button, engine): `exportPermission()` plus ticked export columns
- Subscription gating: optional `moduleKey()` (Suppliers use `inventory`)
- Extra financial columns: `customer.view-financial` / `supplier.view-financial`

## Routes

`POST admin/datatable/{key}/data` (engine only).
`GET`/`POST admin/datatable/{key}/preferences` (engine keys and any listing
table id). Export goes through the module's existing `GET admin/{module}/export`
and, when that module is registered on a DataTable, uses the engine with the
ticked export columns, filters, and search.
