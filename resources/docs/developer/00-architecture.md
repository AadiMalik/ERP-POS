# Architecture & Overview

## Stack

Laravel 8 (PHP `^8.0.2`), server-rendered Blade views (no SPA framework), jQuery +
DataTables for interactive tables, the "Sneat" Bootstrap admin theme (light sidebar,
Public Sans font, configurable per-business theme presets — see
`config/theme_presets.php`). Authentication is Laravel's built-in guard; API tokens
(customer-facing mobile/website auth) use Laravel Sanctum. Authorization uses
`spatie/laravel-permission`, customized (see
[Permissions & Access Control](05-permissions-access-control.md)).

## Brand Assets

Platform branding lives under `public/assets/img/`. Do not drop raw logo files into
views — go through the existing partials:

- `resources/views/partials/favicon.blade.php` — browser tab / apple-touch icons
  (`public/assets/img/favicon/favicon-{16,32,192,512}.png` and `favicon.ico`).
- `resources/views/partials/brand-logo.blade.php` — wordmark/lockup variants:
  `sidebar` (horizontal lockup), `login` (the same 192px tab icon as the favicon,
  sized as a 72×72 rounded square on the auth card), `footer`, and `icon`.
- `resources/views/partials/brand-wordmark.blade.php` — “Dukanaz” text mark used
  next to the login icon.

Guest auth screens (login, forgot password, OTP reset) share
`resources/views/layouts/auth.blade.php` so they never load the admin sidebar.
Password fields across auth **and** the admin panel use
`resources/views/partials/password-input.blade.php` (eye icon show/hide via
`public/assets/js/password-toggle.js`).

OTP emails are sent by `OtpService::send($email, $purpose, $business_id, $channel)`.
`$channel` defaults to `erp`:

- **erp** (`emails.otp` / `emails.otp-text`) — Dukanaz-branded. The horizontal
  lockup is inlined via Laravel's mail `$message->embed()` (with a public URL
  fallback). Used for admin / ERP forgot-password only.
- **storefront** (`emails.otp-storefront` / `emails.otp-storefront-text`) — used
  for all website auth OTPs (`send-otp` onboarding/login and `forgot-password`).
  Branding comes from `Business` (name, logo under `public/uploads/business/`)
  and `WebsiteThemeSetting` (heading/primary/accent colors). Footer is
  **Powered by Dukanaz**; everything else is the tenant business.

The recipient’s name/email are included when a matching `users` row exists, and
the subject/copy change by purpose (`password_reset`, `login`, `onboarding`).
`OtpService` passes that data through `EmailData` to `CommonMail` (public
property `$emailData` — not `$email`, so Blade can use an account-email string
without colliding with the DTO).

The auth layout does **not** load `theme-custom.css` (that file depends on
`--erp-*` variables from the app layout). Login-specific icon sizing is therefore
inlined on the auth layout as well as mirrored under `.dukanaz-brand-img--login`
in `public/assets/css/theme-custom.css`.

## Request Flow / Layering

`Route → Controller → Service → Model`. Controllers
(`app/Http/Controllers/Admin/**`) are thin: they resolve permissions via
constructor middleware, call one or more injected Services, and return a view or
JSON. Business logic, validation orchestration, and multi-step writes live in
**Services** (`app/Services/Concrete/Admin/**`, ~270 classes, one per
domain/controller pairing, e.g. `OrderService`, `GrnService`,
`AccountingPeriodService`). There is no separate Repository layer — Services query
Eloquent models directly. Reports follow the same shape under
`app/Services/Concrete/Admin/Reports/**` (see
[Reports Infrastructure](06-reports-infrastructure.md)).

## Multi-Tenancy — Read This First

Almost every domain table carries a `business_id` (and, for branch-scoped data,
`branch_id`) UUID column — confirmed across the large majority of the 137 models.
**There is no Eloquent global scope or `BelongsToBusiness` trait enforcing this.**
Tenancy isolation is a **convention**, not a framework guarantee: every new query
against a tenant-scoped table must explicitly filter by `business_id`
(`Auth::user()->business_id` is the standard source), or it will leak data across
businesses. When adding a new query, controller action, or report, always check how
the sibling code in the same Service scopes its queries and follow it exactly.

## Primary Keys & Audit Columns

Domain models use **UUID string primary keys**, not auto-increment integers:
```php
protected $primaryKey = 'product_id';
protected $keyType = 'string';
public $incrementing = false;
```
Laravel's native `timestamps()` / `SoftDeletes` are **not** used. Instead, every
table has its own audit block: `date_created`, `date_updated`, `date_deleted`,
`createdby_id`, `updatedby_id`, `deletedby_id`, `is_deleted` — and models set
`public $timestamps = false`. Follow this exact convention (not Laravel's default)
for any new table/model.

## Goods vs. Services — Parallel Table Families

Physical, stock-tracked transactions and non-stock ("service") transactions are
modeled as **separate, parallel families** rather than one polymorphic set:
`purchases`/`purchase_details` vs. `service_purchases`/`service_purchase_details`,
and `orders`/`order_details` (POS) vs. `service_sales`/`service_sale_details` —
each with its own returns tables. Don't try to unify them; this split is
deliberate (Service Management is an independently toggleable module — see
[Subscription & Module Gating](08-subscription-module-gating.md) — and never
touches stock).

## Reports Have No Schema Footprint

There is no `Report` model or migration. All ~90 report classes under
`app/Http/Controllers/Admin/Reports/**` and
`app/Services/Concrete/Admin/Reports/**` are read-only projections over the
transactional tables (orders, journal_entries, purchases, attendances, payslips,
etc.) — nothing is persisted specifically for reporting.

## Key Vendor Packages

| Package | Used for |
|---|---|
| `barryvdh/laravel-dompdf` | All PDF generation (reports, invoices, this documentation portal) |
| `maatwebsite/excel` | Excel/CSV export (report `export`/`export-csv` actions) |
| `spatie/laravel-permission` | Roles & permissions (customized — see [Permissions](05-permissions-access-control.md)) |
| `laravel/sanctum` | Customer-facing API token auth (`routes/api.php`) |
| `league/commonmark` (transitive, via Laravel Mail Markdown) | Renders this Documentation portal's Markdown source |
| `yajra/laravel-datatables` (via `config/datatables.php`) | Server-side DataTables for list screens |

See [Modules, Controllers & Services](03-modules-controllers-services.md) for the
full module map, and [Coding Conventions](11-coding-conventions.md) for the
checklist to follow when adding a new module.
