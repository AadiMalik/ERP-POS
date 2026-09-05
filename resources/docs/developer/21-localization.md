# Localization / Multilingual System

## Shape

Business-scoped, following the standard [Settings System](07-settings-system.md) recipe:
`LocalizationSetting` (one row per business — `display_language`, `input_language`,
`direction_override`) is exactly one more `xxx_settings` table alongside `BusinessSetting`,
`ThemeSetting`, etc. Managed from **Business Settings → Language / Localization**
(`resources/views/admin/setting/tabs/localization.blade.php`), gated by the same shared
`setting.manage` permission as every other tab — no new permission was introduced.

## Language Registry

`config/languages.php` is the single source of truth for every selectable language: locale
code, English `name`, `native_name`, `direction` (`ltr`/`rtl`), and `fallback` (always `en`).
Adding language #N later is a **config-only** change — no core code touched. A language is
immediately selectable the moment it's added here, whether or not any `lang/{code}/*.php`
content exists yet for it (see Fallback below).

## Applying the Locale Per Request

`App\Http\Middleware\SettingMiddleware` (already the single place every other per-business
setting gets hydrated into `session()` on each request) now also:
```php
$localization_setting = $business->localizationSetting?->toArray() ?? [];
App::setLocale($localization_setting['display_language'] ?? 'en');
session(['localization_setting' => $localization_setting]);
```
Any controller reached through a route carrying the `setting` middleware alias gets the
correct locale automatically. **`HomeController` (`/home`, the dashboard) registers `setting`
in its constructor** the same way it registers `auth`/`check.subscription` — it sits outside
the `admin`-prefixed route group (its URL must stay `/home`, not `/admin/home`), so it needs
this applied directly rather than by being inside that group.

`resolved_text_direction()` (`app/Helpers/CommonFunctions.php`, alongside the existing
`resolved_theme_setting()`) resolves the effective `ltr`/`rtl` direction: `direction_override`
wins if set to `ltr`/`rtl`; `auto` (the default) derives it from the active `display_language`'s
registry entry. `layouts/app.blade.php` sets `<html dir="{{ resolved_text_direction() }}">`
alongside the pre-existing `lang="..."` attribute, and a `data-input-dir` body attribute
(derived the same way from `input_language`, independent of the overall interface direction)
drives the Default Input Language behavior (see below).

Super Admin (`business_id` is `null`) and pre-auth pages (login/register) have no business
context to key a locale off — they stay on `config('app.locale')` (`en`), same as every other
per-business setting today.

## Fallback (Missing Translations)

There is **no custom fallback-merging code** — this is Laravel's own `fallback_locale`
mechanism (`config('app.fallback_locale')`, already `en`). Any `__('module.key')` call that
doesn't exist in the active locale's file automatically re-resolves against `en` instead of
printing the raw key. This is exactly why registering a new language in `config/languages.php`
is safe immediately: every string just renders in English until someone adds real content.

## Translation File Structure

One PHP array file per module per locale under `lang/{locale}/`, e.g. `lang/en/common.php`,
`lang/en/sidebar.php`, `lang/en/dashboard.php`, `lang/en/settings.php`,
`lang/en/products.php`, `lang/en/units.php`, `lang/en/categories.php`,
`lang/en/brands.php`, `lang/en/customers.php`, `lang/en/suppliers.php`,
`lang/en/warehouses.php`, `lang/en/purchases.php`, `lang/en/purchase_returns.php`,
`lang/en/orders.php`, `lang/en/order_returns.php`, `lang/en/sub_categories.php`,
`lang/en/opening_stock.php`, `lang/en/transfer_notes.php`, `lang/en/stock_taking.php`,
`lang/en/good_receipt_notes.php`, `lang/en/waste_damage_expiry.php`, `lang/en/reports.php`,
plus billing/CMS shells (`packages`, `subscriptions`, `subscription_invoices`,
`subscription_renewal_requests`, `my_subscription`, `subscription_settings`,
`payment_gateway`, `payment_transaction`, `activity_log`, `login_history`,
`notifications`, `notification_templates`, `broadcast_notifications`, `documentation`,
`backups`, `profile`, `product_reviews`, `admin_expenses`, `website_cms`), plus
accounting/users/HRM files (`accounts`, `account_types`, `account_sub_types`, `journals`,
`journal_entries`, `vouchers`, `expenses`, `expense_categories`, `customer_payments`,
`supplier_payments`, `bank_reconciliation`, `budgets`, `users`, `roles`, `branches`,
`discounts`, `payment_methods`, `hrm_employees`, `hrm_departments`, `hrm_designations`,
`hrm_shifts`, `hrm_attendance`, `hrm_leaves`, `hrm_payroll`, `hrm_payslips`,
`hrm_salary_structures`, `hrm_salary_components`, `hrm_ess`, `hrm_advances`,
`hrm_deductions`, `hrm_exit`, `hrm_assets`, `hrm_ledger`), plus POS / manufacturing / assets /
period-closing files (`pos`, `manufacturing`, `serial_numbers`, `variation_batches`,
`variation_stocks`, `variation_unit_conversions`, `loss_reasons`, `barcodes`,
`service_purchases`, `service_sales`, `service_purchase_returns`, `service_sale_returns`,
`fixed_assets`, `fixed_asset_categories`, `fixed_asset_depreciations`, `fiscal_years`,
`accounting_periods`, `period_closing_rules`, `recurring_transactions`), plus purchasing
request files (`purchase_requests`, `purchase_request_quotations`) — plus Laravel's
own stock `auth.php`/`validation.php`/`passwords.php`/`pagination.php` (translated per
locale too, so every existing `$request->validate([...])` call gets translated
field-level errors for free, zero controller changes). A handful of short UI phrases
already called via the plain (non-namespaced) `__('Logout')`-style JSON convention
(auth screens, the navbar, and some zero-blade page titles) are covered by
`lang/{locale}.json` (or fall back to the English literal).

Shared CRUD labels (filters, save, close, name, business, etc.) live in `common.php` and
are reused via `__('common.key')`. Module-specific titles/columns use `__('units.key')` /
`__('categories.key')` / `__('brands.key')` / `__('products.key')` /
`__('customers.key')` / `__('suppliers.key')` / `__('warehouses.key')` /
`__('pos.key')` / `__('manufacturing.key')` / etc. Report screens use shared
`__('reports.*')` for titles/chrome plus `__('common.*')` for filters/export buttons.
Modal titles and dynamic JS strings are exposed as `window.i18n_{module}` from the Blade
`@section('js')` (or inline on quick-create partials).

**Converted list/create screens so far**:
- **Catalog / party masters**: Products, Units, Categories, Brands, Sub Categories,
  Customers, Suppliers, Warehouses (including show + quick-create where those views exist).
- **Purchasing / sales / inventory**: Purchases, Purchase Returns, Orders
  (list/show/print — not the full POS screen), Order Returns, Opening Stock, Transfer Notes,
  Stock Taking, Goods Receipt Notes, Waste/Damage/Expiry — index + create + print blades
  where present; JS strings via `window.i18n_*` on indexes and external JS (`order.js`,
  `sub_category.js`).
- **POS / manufacturing / stock helpers / assets / periods (this batch)**:
  - POS Registers (+ modal + `pos-register.js`), Register Sessions (+ void confirm via
    `window.i18n_pos` / `pos-register-session.js`), Order History (chrome + receive-payment
    modal + toasts via `order-history.js`), POS select-context, **POS screen static chrome**,
    and **deep cart/payment/toast JS** in `pos-screen.js` (stock hints, session open/close,
    cash movement/expense, voucher/serials, hold/pay/correct, receipt print errors) via
    `window.i18n_pos` from `trans('pos')` (+ selected `common.*` keys). Thermal session
    summary print labels wrapped. Product names / payment-method / order-type / category
    names from the DB remain as stored master data (not UI chrome).
  - Manufacturing plans/productions indexes + recipe create; create/show page titles.
  - Serial Numbers index (+ found-unit modal + JS toasts), Variation Batches / Stocks /
    Unit Conversions indexes (+ JS modal titles via `window.i18n_*`).
  - Loss Reasons index/modal, Barcode label print title.
  - Service Purchase/Sale/Return indexes (+ create page titles).
  - Fixed Assets / Categories / Depreciation indexes (+ create/show titles).
  - Fiscal Years, Accounting Periods, Period Closing Rules (list + JS), Recurring
    Transactions index (+ create/history titles).
- **Accounting / finance**: Chart of Accounts (`accounts`), Account Types,
  Account Sub Types, Journals (+ Journal Entries lang file), POS Vouchers, Expenses,
  Expense Categories, Customer Payments, Supplier Payments, Bank Reconciliation, Budgets.
- **Users / settings-adjacent**: Admin Users, Roles, Branches, POS Discounts, POS Payment
  Methods. **Business Settings — all tabs** converted via `lang/{locale}/settings.php`
  (`settings.*`, **633 keys × 46 locales**): page title + left nav, Business, Localization,
  Accounting (COA defaults + automation/budgeting), Inventory, Customer, Supplier, Email,
  SMS, WhatsApp, Firebase, FBR, PRA, Notification, POS, Print (header/footer/page/body),
  Thermal Print, Barcode & QR, Theme / Appearance, Website Theme, and Website Settings.
  Blades use `__('settings.*')`; missing keys fall back to English via Laravel's
  `fallback_locale`.
- **HRM core (priority screens)**: Employees, Departments, Designations, Shifts
  (create + quick-create), Attendance (list/create/report), Leave Requests / Leave Types
  (list + create, approve/reject JS via `window.i18n_hrm_leaves`), **Payroll**
  (index/create/show + finalize/reopen/pay confirms via `window.i18n_hrm_payroll`),
  **Payslips** (show + PDF), **Salary Structures** (index/manage), **Salary Components**
  (index/create), **ESS** (dashboard, attendance, leave, advance, payslip, exit, profile
  + leave-cancel toasts via `window.i18n_hrm_ess`), Employee Advances
  (index/create + decide JS via `window.i18n_hrm_advances`), Employee Deductions
  (index/create), Resignation/Termination (index/create/show + clearance/finalize JS via
  `window.i18n_hrm_exit`), Employee Ledger index, Assets + Asset Allocation
  (index/create + return prompt via `window.i18n_hrm_assets`), department/designation
  quick-create modals.
- **Reports (this batch)**: All **124** report **index** blades under
  `resources/views/admin/reports/` — shared chrome (filters, print/pdf/excel/csv, search/
  reset, common filter labels/placeholders) via `__('common.*')` / `__('reports.*')`, plus
  page titles. Print/PDF blades: **titles/headers** converted where practical (≈240 files);
  column header rows and dense table body labels inside print/pdf are **not** fully
  converted yet.
- **Packages / subscriptions / billing**: Packages (index + create shell), Subscriptions
  index metrics/links, Subscription Invoices, Renewal Requests, My Subscription, Subscription
  Settings, Payment Gateways, Payment Gateway Transactions.
- **Misc admin shells**: Activity Log, Login History, Notifications, Notification Templates,
  Broadcast Notifications, Documentation center, Backups (+ settings), Profile edit + force
  password, Product Reviews, Admin Expenses index.
- **Website CMS**: website_page/faq/testimonial/hero_stat/section/benefit, contact_message,
  newsletter_subscriber, social_media — index titles + common chrome; Intro CMS indexes got
  common chrome (filters/add-new); many intro-specific titles remain English.

Module PHP files for the above exist under every locale directory that has `common.php`
(all languages registered in `config/languages.php`). Titles and high-traffic phrases have
real translations; some compound form strings in non-primary locales still use a
phrase/word-bank hybrid and may read awkwardly until polished. Report titles often reuse
the matching `sidebar.php` translation per locale. Locales without a given
module file still fall back to `en`.

**Still largely English (UI chrome not yet wrapped)** — continue with the same recipe:
dense product-table JS inside Purchase Request / Quotation creates and their print/pdf
bodies, Bank Reconciliation workspace/create/PDF polish, Budget modal option labels beyond
the shell, **report print/pdf column headers and result-table labels**, Packages create deep
field labels, subscription renew/show/invoice detail blades, Intro CMS titles beyond chrome,
and Business manage deep fields. POS cashier UI (screen JS toasts/cart/payment + blades
listed above) is largely converted; remaining POS English is mostly DB-sourced labels
(product/category/payment-method names) and any server `Message` strings not localized.
**To translate a new module** (the exact recipe used for `products.php` / `units.php` /
`categories.php` / `brands.php` / `customers.php` / `suppliers.php` / `warehouses.php` and
the accounting/HRM files above): create
`lang/en/{module}.php` with English defaults, wrap every hardcoded string in the relevant
Blade view(s) with `{{ __('{module}.key') }}` (prefer `__('common.*')` for shared labels),
pass `importExportLabel` as `__('{module}.title')`, expose JS strings via
`window.i18n_{module}`, then create the same-shaped `lang/{locale}/{module}.php` per
language you want real content for. No route, controller, or architecture change needed.

## App-Wide Toast/Response Messages (High Leverage)

`App\Traits\ResponseAPI::coreResponse()` is the single formatter behind every controller's
`success()`/`error()`/`validationResponse()` JSON response across the **entire** app. It now
runs `$message = __($message);` before returning it — since `__()` on a plain string with no
`file.key` dot-notation checks `lang/{locale}.json` and falls back to the literal string
unchanged if no match exists, this is 100% backward compatible and, with the ~10
`App\Enums\Message` constants (`Message::SUCCESS`, `Message::SAVE`, `Message::UPDATE`, etc.)
added to `lang/{locale}.json`, translates every save/update/delete/error toast **app-wide**
from one file, not per-controller.

Similarly, `public/assets/js/universal.js`'s `deleteRecord()` (the shared delete-confirmation
function every "Delete" button on every list screen calls) and the generic `ajaxRequest()`
error fallback now read their default strings from a small `window.i18n` object
(populated once in `layouts/app.blade.php` from `lang/en/common.php`-backed keys) instead of
hardcoded English — one function, every delete confirmation across the ERP.

## RTL Layout

The Sneat theme ships no RTL CSS build (only `core.css`/`theme-default.css` compiled output,
no SCSS source to recompile from). `public/assets/css/rtl-overrides.css` is a hand-authored
overlay, scoped entirely under `html[dir="rtl"]`, conditionally linked in
`layouts/css.blade.php` only when `resolved_text_direction() === 'rtl'`. It covers sidebar
position, menu icon spacing, text alignment, dropdown/modal alignment, and DataTables/Select2
alignment — a functional layout-level mirror, not a pixel-perfect recompile of every Sneat
class.

## Default Input Language

A body-level `data-input-dir` attribute (set from `input_language`, independent of
`direction_override`) plus a small `$(document).ready()` block in `layouts/js.blade.php` sets
`dir="rtl"` on generic text inputs/textareas when the input language is RTL. Fields that must
always stay LTR regardless (SKU/barcode, price, quantity, email, phone, URL) carry a
`.ltr-field` class — see `resources/views/admin/product/create.blade.php`'s `name`/`slug`
inputs for the pattern to follow on other forms.

## DataTables / Select2 / Flatpickr

All three are CDN-loaded (`layouts/js.blade.php`/`css.blade.php`), and each ships official
per-locale language files on the same CDNs already in use — no new dependency:
- DataTables: `resources/views/admin/partials/datatable.blade.php` uses
  `datatablesLocaleCode()` (`app/Helpers/CommonFunctions.php`) to map our
  `config/languages.php` codes onto DataTables CDN filenames (e.g. `zh-CN` → `zh`,
  `zh-TW` → `zh-HANT`). When the helper returns `null` (English, or a locale with no
  CDN pack such as `bal`/`sd`), `language.url` is omitted entirely — otherwise
  DataTables shows warning tn/21 ("i18n file loading error"). Do **not** pass
  `@json(array_merge(...))` inline in Blade: `@json` splits on commas and breaks
  nested arrays; build the PHP array in `@php` first, then `@json($var)` (see
  `admin/pos/screen/index.blade.php`).
- Select2: `dist/js/i18n/{locale}.js` loaded conditionally in `js.blade.php`; pass
  `language: CURRENT_LOCALE` (a JS constant set in `js.blade.php`) to each `.select2({...})`
  call — see `admin/product/index.blade.php` for the pattern.
- Flatpickr: `dist/l10n/{locale}.js` loaded conditionally; the shared `.datepicker` init in
  `js.blade.php` passes `locale: CURRENT_LOCALE`.

## Worked Example: Products Module

`resources/views/admin/product/index.blade.php` and `create.blade.php` are fully converted
(list, filters, DataTable columns, modals, delete/backfill confirmations, and the structural
create/edit form: headings, section headers, primary field labels, type/usage-type options,
visibility checkboxes, footer buttons) as the reference implementation. Newer modules follow
the same recipe at varying depth (list shells + JS strings first; deep create/print blades
next).

## Known Limitations

- Depth varies by module. **Deepest create-form conversions (this batch)**: Purchases
  (labels + JS chrome via `window.i18n_purchases`), Customers / Suppliers / Warehouses,
  Journal Entry create (+ `journal_entry.js` toasts), Expense / Customer Payment /
  Supplier Payment create field labels, Voucher create modal labels, HRM Employee /
  Department / Designation create, Users / Roles create, Purchase Request + Quotation
  create titles/primary labels, and POS screen chrome plus deep cart/payment/toast JS
  (`window.i18n_pos` / `pos-screen.js`). Customer/Supplier/Warehouse create blades were
  already deep and remain so.
- **HRM**: payroll / payslips / salary structures / salary components, ESS, advances, and
  asset allocation screens are converted (wrapped via `__('hrm.*')` / related modules).
- **POS**: cart / payment / toast / confirm JS via `window.i18n_pos` (`pos-screen.js`), plus
  order-history / register-session void / thermal session summary. Master-data names from the
  DB and backend API `Message` payloads remain English.
- **Purchase Requests / Quotations**: create titles + primary header fields converted;
  dense product-table JS and print/pdf bodies still mostly English. New modules:
  `purchase_requests.php`, `purchase_request_quotations.php` (all locales).
- **Zero-`__()` blades**: many remaining shells now have at least a page title and/or
  primary Cancel/Save button wrapped (often via `__('Literal')` JSON-style fallback).
  Field bodies and DataTables cell content on some screens remain English beyond chrome.
- **Accounting leftovers**: Bank Reconciliation workspace/create/PDF, Budget option labels
  beyond shell, journal entry print, payment print templates.
- **Reports**: index shells + print/PDF titles converted; print/PDF column headers use
  `reports.col_*` (synced across all `common.php` locales). Some report-specific filter
  option labels / DataTable column titles may still be English. Billing/CMS/Intro deep
  create/detail blades only partially done.
- Module title/key translations exist for all `common.php` locales; polish quality is highest
  for ar/ur/fa/fr/de/es/zh-CN and title phrases — compound leftovers and newly synced keys
  may still be English fallback until polished. Report titles lean on per-locale `sidebar.php`
  where keys align. Some non-primary locales' `validation.php` were reset to the English
  structure after a flatten accident — Laravel fallback still works; re-translate those
  nested files when polishing.
- Super Admin screens and pre-login pages don't participate in per-business locale.
- Product/Category/Brand/Unit "multilingual master data" (translatable field values, not UI
  chrome) is not implemented — a natural future extension would be a nullable JSON
  `translations` column per model (no new package needed), deliberately not built speculatively.
