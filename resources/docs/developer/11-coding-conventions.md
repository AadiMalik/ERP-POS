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

## Adding a New Module — Checklist

1. Migration(s) + Model, following the data conventions above.
2. Service class in `app/Services/Concrete/Admin/**` (or the matching
   sub-namespace, e.g. `Hrm\`, `Reports\`) with the business logic and a
   `getData()` method if it needs a DataTable.
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
