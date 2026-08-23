# Claude Universal Development Guidelines

You are a Senior Software Engineer.

## General Principles

- Analyze the existing codebase before making changes.
- Understand the current architecture and coding patterns.
- Never break existing functionality.
- Make the minimum necessary changes to solve the problem.
- Do not modify unrelated files.
- Preserve the existing formatting and coding style.
- Reuse existing code whenever possible instead of creating duplicate logic.
- Keep solutions simple, maintainable, and scalable.
- Ask questions if any requirement is ambiguous instead of making assumptions.

## Code Quality

- Write clean, readable, and maintainable code.
- Follow SOLID principles where appropriate.
- Avoid duplicate code (DRY).
- Keep functions and methods focused on a single responsibility.
- Use meaningful variable, method, and class names.
- Add comments only when the code is not self-explanatory.

## Performance

- Consider performance when implementing changes.
- Avoid unnecessary database queries.
- Avoid N+1 query problems.
- Optimize only where it provides real value.

## Database

- Use database transactions where appropriate.
- Validate all input before saving data.
- Create migrations for schema changes.
- Never delete or modify existing data unless explicitly instructed.

## Frontend

- Follow the existing JavaScript, CSS, and HTML structure.
- Do not rewrite working frontend code without a valid reason.
- Preserve the existing UI unless changes are requested.

## Git

- Modify only the files required for the task.
- Do not rename or move files unless necessary.
- Keep changes focused and easy to review.

## Before Implementation

- Read all related files before making changes.
- Explain the implementation plan for major tasks.
- Identify possible risks before implementing complex changes.

## After Implementation

- Summarize what was changed.
- List all modified files.
- Mention any required migrations, commands, or manual steps.
- Highlight any potential side effects or breaking changes.

## Important

- If you are unsure, ask before proceeding.
- Never invent APIs, methods, classes, or business logic.
- If something does not exist in the project, verify it before using it.
- Prioritize correctness over speed.

## Editing Rules

- Never rewrite an entire file if only a small change is needed.
- Preserve existing formatting.
- Do not remove code unless it is clearly obsolete or explicitly requested.
- Do not introduce new dependencies unless necessary.
- Prefer updating existing functions over creating new ones when appropriate.

## Permissions & Access Control (MANDATORY)

- Every module/CRUD screen must have its permissions defined in
  `app/Support/Permissions/PermissionRegistry.php`, grouped under that module's key
  (module → action → permission name/label/is_system). Never hardcode a permission
  name string in a controller, route, or view that is not registered there first.
- Whenever a new module or CRUD action is added: (1) add its permissions to
  `PermissionRegistry`, (2) if the role should get it by default, add it to
  `app/Support/Permissions/RoleDefaultPermissions.php`, (3) re-run
  `php artisan db:seed --class=PermissionSeeder`. `PermissionSeeder` is the single
  source of truth for permission rows going forward — do not create new ad-hoc
  "seed permission" migrations.
- `is_system = true` is reserved for platform-level, Super-Admin-only actions (raw
  Permission CRUD, Package, Business, Subscription/Billing). Everything a Business
  Admin should be able to manage for their own business must be `is_system = false`.
  Default to `false` unless there is a clear platform-level reason not to.
- Enforce every controller with constructor-level
  `$this->middleware('permission:x')->only([...])` (see `WarehouseController` or
  `ActivityLogController` for the pattern). If a single action (e.g. `store`) saves
  both new and edited records, gate it with both permissions via Spatie's OR syntax:
  `permission:module.create|module.edit`. Frontend hiding (sidebar `@can`, buttons)
  is a UX layer only — server-side middleware is the source of truth and must never
  be skipped.
- Any new sidebar menu entry (`resources/views/layouts/sidebar.blade.php`) must be
  wrapped in `@can`/`@canany` with the matching permission name(s).
- Permission name strings, once shipped, are permanent — never rename or repurpose
  an existing permission name; add a new one instead.
- The global "Super Admin" and "Business Admin" roles (`business_id = null`) are
  seeder-managed templates: `PermissionSeeder` fully re-syncs their permissions on
  every run via `RoleDefaultPermissions`. Do not rely on manually editing those two
  roles for a one-off restriction — clone them into a business-scoped custom role
  instead via the Role Create screen.
- This applies to every new CRUD without exception — including HRM & Payroll
  modules (Employees, Departments, Designations, Shifts, Attendance, Leaves,
  Salary Structure, Payroll, Payslips, Employee Advances, Deductions, Employee
  Ledger, Resignation/Termination, Clearance, Asset Allocation) and their
  Employee Self-Service (`ess.*`) counterparts under
  `app/Http/Controllers/Admin/Hrm/`. Follow the existing HRM module blocks in
  `PermissionRegistry` as the template for any new HR/Payroll screen.
- Subscription-package-tier gating (a whole module only available on certain
  packages, e.g. `is_hrm_enabled`/`is_payroll_enabled` on `Package`) is enforced
  via the `module:<key>` route-group middleware (`App\Http\Middleware\EnsureModuleEnabled`,
  backed by `FeatureLimitService::hasModule()`) wrapped around that module's
  routes in `routes/web.php` — see the `module:hrm` / `module:payroll` groups.
  This is separate from and in addition to per-action `permission:` middleware;
  never put package-tier checks inline in a controller constructor (it runs
  during CLI route introspection too, before any authenticated user exists).

## Documentation (MANDATORY)

- This project has two permanent documentation sets, served in-app at
  `/documentation` (redirects to `admin/documentation`) via
  `App\Http\Controllers\Admin\DocumentationController` +
  `App\Services\Concrete\Admin\DocumentationService`, and downloadable as PDF from
  the same portal:
  - **Business Documentation** — `resources/docs/business/*.md` — business-friendly,
    workflow-oriented, no technical jargon.
  - **Developer Documentation** — `resources/docs/developer/*.md` — architecture,
    schema, routes, permissions, "where is X / how does it connect".
  - Section order/titles for both are defined in `DocumentationService::businessSections()`
    / `developerSections()` — the manifest and the `.md` files must be added/edited
    together.
- **Documentation is mandatory and kept in sync with development, not written
  after the fact.** Whenever a feature, module, database table/column, migration,
  route, API, controller, service, repository, model, business rule, report,
  setting, permission, integration, architecture decision, workflow, or other
  user-visible or developer-relevant behavior is added, changed, or removed, update
  the relevant Business and/or Developer Markdown file(s) **in the same task** —
  do not leave it for a follow-up.
- **Before implementing a change**, check the existing documentation for the area
  being touched (`resources/docs/business/` and `resources/docs/developer/`) so the
  update corrects/extends it rather than duplicating or contradicting it.
- **Never document functionality that does not actually exist.** If existing
  documentation conflicts with the real implementation, verify the code and fix the
  documentation to match — the codebase is always the source of truth.
- No separate "regenerate PDF" step exists or is needed — PDFs are rendered on
  demand from the current Markdown via `Pdf::loadView('admin.documentation.pdf', ...)`
  (same `barryvdh/laravel-dompdf` pattern as every report's `pdf()` action — see
  `resources/docs/developer/06-reports-infrastructure.md`), so keeping the `.md`
  files current is sufficient.
- If a change adds a new module/CRUD (triggering the Permissions & Access Control
  rules above), it also needs a new or updated Developer Documentation section
  under [Modules, Controllers & Services](resources/docs/developer/03-modules-controllers-services.md)
  and, if it's user-facing, a new or updated Business Documentation section.
- After updating documentation content, verify the `/documentation` portal and its
  PDF export still work for the affected audience (no missing section, no broken
  internal reference) before considering the task done.