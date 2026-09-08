# Coding Conventions

Full detail lives in the project's `CLAUDE.md` — this page is the quick-reference
version for a developer already inside the codebase.

## Layering
`Route → Controller → Service → Model`. Controllers stay thin (permission
middleware + calling the Service); business logic lives in Services; no separate
Repository layer.

## Data Conventions (apply to every new table/model)
- UUID string primary key (`$incrementing = false`, `$keyType = 'string'`).
- Custom audit columns (`date_created`, `date_updated`, `date_deleted`,
  `createdby_id`, `updatedby_id`, `deletedby_id`, `is_deleted`) instead of
  Laravel's `timestamps()`/`SoftDeletes` — set `public $timestamps = false`.
- `business_id` (and `branch_id` where relevant) on every tenant-scoped table —
  and **always filter by it explicitly in every query**; there is no global scope
  doing this for you. See [Architecture & Overview](00-architecture.md).

## UTC Storage / Business Timezone Display

The database always stores UTC. The Business's configured timezone
(`business_settings.timezone`, edited via Settings → Business, backed by the
`timezones` table seeded from PHP's IANA identifier list — DST is handled
correctly because conversion always goes through a real timezone identifier,
never a fixed offset) is the single source of truth for how dates/datetimes are
shown to and entered by users. Never write ad-hoc `Carbon::parse(...)->setTimezone(...)`
or manual offset math in a controller/view — always go through the global helpers in
`app/Helpers/CommonFunctions.php`:

- **Genuine timestamps** (a real time-of-day: `date_created`, `date_updated`,
  `order_date`, `payment_confirmed_at`, any workflow `*_at` column) —
  `utcDateTime($input)` to convert a business-local input to UTC before saving,
  `localDateTime($stored)` to convert a UTC value back to business-local for
  display/edit-form population. `businessStartOfDay($date = null)` /
  `businessEndOfDay($date = null)` give the UTC instant bounding a business-local
  calendar day — use these (not `Carbon::parse($x)->startOfDay()`/`Carbon::today()`)
  for any "from/to date" range filter or "today" quick-filter compared against a
  timestamp column.
- **Pure calendar-date fields** (no time-of-day is ever entered — a date-only
  picker: `purchase_date`, `expense_date`, `sale_date`, `expiry_date`, `due_date`,
  etc.) are **not timezone-dependent** and must not be converted through a
  timezone-instant shift — use `utcDate($input)` / `businessDate($stored)`
  instead, which just reformat between the business's `date_format` and the DB's
  `Y-m-d`. (A previous bug here — fixed — ran these through a full UTC-instant
  conversion, which silently shifted the stored/displayed date backward by one
  day for any positive-UTC-offset business timezone, depending on what time of
  day the save happened; `businessToday()` — a `'Y-m-d'` string in the business
  timezone — replaces `Carbon::today()` as the "what day is it" default/comparison
  for these fields.)
- **Native `<input type="datetime-local">` fields** (fixed `Y-m-d\TH:i` wire
  format regardless of the business's configured `date_format`/`time_format`) use
  the separate `utcDateTimeLocal($value)` / `localDateTimeLocal($stored)` pair.
- `businessTimezone($override = null)` is the resolver every helper above uses
  internally (session, for the admin web session → the authenticated user's own
  business → app default) — pass `$override` explicitly wherever there's no HTTP
  session to read from (a console command iterating multiple businesses, for
  example — see `CheckNotificationAlertsCommand`).

API/mobile JSON responses return raw UTC (ISO-8601 where explicitly formatted,
e.g. `CustomerOrderService`); see [Routes & APIs](04-routes-apis.md).

**MySQL gotcha - always give a TIMESTAMP column an explicit default.** This
server has `explicit_defaults_for_timestamp` OFF (MySQL/MariaDB's legacy
default), so the *first* non-nullable `TIMESTAMP` column in a table silently
gets `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` attached by the
server itself, regardless of what the migration asked for - and then MySQL
rewrites that column to "now" (in the *server's* local system timezone, not
this app's UTC contract) on every subsequent update to the row, silently
corrupting a value the application already explicitly manages. Found and
fixed in `2026_09_09_000000_fix_implicit_on_update_current_timestamp_columns.php`
(`orders.order_date`, `otps.expires_at`,
`payment_gateway_webhook_logs.received_at`,
`pos_register_sessions.opening_datetime` - all previously silently rewritten
on unrelated updates to their row). When adding a `$table->timestamp(...)`
column that isn't a Laravel-managed `created_at`-style column, either make it
`->nullable()` (matching `date_created`/`date_updated`) or give it an
explicit `->default(null)`/raw `DEFAULT CURRENT_TIMESTAMP` (no `ON UPDATE`) -
never leave a business-meaning timestamp column with no explicit default.

## Adding a New Module — Checklist

1. Migration(s) + Model, following the data conventions above.
2. Service class in `app/Services/Concrete/Admin/**` (or the matching
   sub-namespace, e.g. `Hrm\`, `Reports\`) with the business logic. For a new
   list screen, add an `app/DataTables/FooDataTable.php` definition, register
  it in `DataTableRegistry`, and render `<x-erp-data-table key="foo" />` on
  the index view — see
  [Centralized DataTable System](24-datatable-system.md). Existing unmigrated
  modules still use a Service `getData()` method; **Customize Table** still
  attaches automatically as long as the HTML table has an `id`.
3. Controller in `app/Http/Controllers/Admin/**`, thin, with constructor
   `permission:` middleware on every action (see
   [Permissions & Access Control](05-permissions-access-control.md)).
4. Permissions added to `PermissionRegistry` **first**, then
   `php artisan db:seed --class=PermissionSeeder`. If it should be default-on for
   broad manager roles, add its module key to `operationalModuleKeys()`.
5. Routes in `routes/web.php`, following an existing group as the template. If it's
   a subscription-gated module, add it to `SubscriptionModuleRegistry` and wrap the
   group in `module:<key>` (see
   [Subscription & Module Gating](08-subscription-module-gating.md)).
6. Views under `resources/views/admin/<module>/**`; sidebar entry wrapped in
   `@canAccess`/`@canAccessAny`.
7. **Update the documentation** — the relevant Business and/or Developer Markdown
   file(s) under `resources/docs/`, in the same task (mandatory — see CLAUDE.md
   and [The Documentation System Itself](12-documentation-system.md)).

## General Rules (from CLAUDE.md)
- Never break existing functionality; make the minimum change needed.
- Reuse existing Services/helpers rather than duplicating logic.
- Preserve existing formatting/coding style; don't reformat unrelated code.
- Use database transactions for multi-step writes; validate all input.
- Never rename or repurpose a shipped permission name.

## Page action lock (frontend)

Do **not** add per-CRUD page overlays or custom double-submit loaders. The global
`PageActionLock` covers listing/create/edit actions automatically.

When a control must stay clickable during another request (rare), mark it:

```html
<button type="button" data-action-lock="off">...</button>
```

When a custom confirm dialog is used before a mutating request, either:
- include `delete` / `approve` / `reject` in the control `id`/`class`, or
- set `data-confirm` / `data-action-confirm`, or
- call `PageActionLock.softGate(btn)` on open and
  `PageActionLock.confirmAccepted(btn)` after confirm.

Background polls / Select2 remote searches are ignored by URL pattern; for other
noise use `skipActionLock: true` on the jQuery/`fetch` options.

Frontend locking does **not** replace server-side guards — keep
`DB::transaction`, status checks (“already paid/posted”), and validation on
duplicate-sensitive writes (payments, postings, approvals).
