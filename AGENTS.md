---

description: Universal development rules for this project
alwaysApply: true
-----------------

# Universal Development Guidelines

You are acting as a **Senior Software Engineer** working on this project.

## 1. Mandatory Project Instructions

Before making any change:

1. Read the project's `CLAUDE.md` file if it exists.
2. Treat `CLAUDE.md` as the project's primary project-specific source of truth.
3. Follow these Cursor rules together with `CLAUDE.md`.
4. If a user instruction explicitly conflicts with an existing rule, follow the user's explicit instruction for that task unless doing so would create a security or data-integrity problem.
5. Never invent project architecture, APIs, methods, classes, database tables, routes, permissions, or business logic.

---

# 2. General Principles

* Analyze the existing codebase before making changes.
* Understand the current architecture and existing coding patterns.
* Read all relevant files before modifying them.
* Never break existing functionality.
* Make the minimum necessary changes required to solve the problem.
* Do not modify unrelated files.
* Preserve existing formatting and coding style.
* Reuse existing code whenever possible.
* Avoid duplicate logic.
* Keep solutions simple, maintainable, and scalable.
* Ask for clarification when a requirement is genuinely ambiguous.
* Prefer correctness over speed.
* Never make assumptions about functionality that has not been verified in the codebase.

---

# 3. Code Quality

* Write clean, readable, maintainable code.
* Follow SOLID principles where appropriate.
* Follow DRY principles.
* Keep functions and methods focused on a single responsibility.
* Use meaningful variable, method, class, component, and function names.
* Keep files and components reasonably sized.
* Add comments only when the code is not self-explanatory.
* Follow the existing project's coding conventions.
* Prefer existing helper methods, services, repositories, components, traits, and utilities over creating duplicates.

---

# 4. Performance

Consider performance for every implementation.

* Avoid unnecessary database queries.
* Avoid N+1 queries.
* Use eager loading where appropriate.
* Avoid unnecessary API requests.
* Avoid unnecessary frontend re-renders.
* Do not add premature optimization without a real performance reason.
* Optimize code when there is a measurable or obvious benefit.

---

# 5. Database Rules

* Create migrations for database schema changes.
* Never modify database schema manually when a migration is appropriate.
* Use database transactions where multiple related database operations must succeed or fail together.
* Validate all input before saving data.
* Never delete or modify existing data unless explicitly requested.
* Do not change existing columns, relationships, indexes, or constraints without checking their current usage.
* Before adding a new table or column, inspect whether equivalent functionality already exists.
* Preserve existing data integrity and relationships.
* Never assume a database column exists; verify the migration/model/schema first.

---

# 6. Backend / Laravel Rules

When working on Laravel code:

* Follow the existing Laravel architecture.
* Inspect existing controllers, services, repositories, models, requests, routes, policies, middleware, and views before creating new ones.
* Reuse existing services and patterns where possible.
* Keep business logic out of controllers when the project architecture already uses services/repositories.
* Use Form Requests or the project's existing validation pattern for validation.
* Use Eloquent relationships instead of unnecessary raw queries when appropriate.
* Preserve existing route naming and middleware conventions.
* Never create duplicate business logic in multiple controllers/services.
* Never invent an API endpoint, model relationship, service, or method without verifying it exists or is required.
* Use existing authentication, authorization, tenant/business, and permission architecture.

---

# 7. Permissions & Access Control — MANDATORY

Every module and CRUD screen must have its permissions defined in:

`app/Support/Permissions/PermissionRegistry.php`

Permissions must be grouped under the appropriate module key:

`module → action → permission name/label/is_system`

Never hard-code a permission name in a controller, route, or view unless that permission is registered in `PermissionRegistry.php`.

## New Module / CRUD Permission Process

Whenever a new module or CRUD action is added:

1. Add its permissions to `PermissionRegistry.php`.
2. If the role should receive the permission by default, add it to:
   `app/Support/Permissions/RoleDefaultPermissions.php`
3. Run:

```bash
php artisan db:seed --class=PermissionSeeder
```

`PermissionSeeder` is the single source of truth for permission rows.

Do not create ad-hoc permission seed migrations.

## is_system

* `is_system = true` is reserved for platform-level Super Admin-only functionality.
* Examples include:

  * Permission CRUD
  * Package
  * Business
  * Subscription/Billing
* Business Admin functionality must normally use:
  `is_system = false`
* Default to `false` unless there is a clear platform-level reason to use `true`.

## Controller Authorization

Every controller action must be protected using constructor-level middleware:

```php
$this->middleware('permission:x')->only([...]);
```

Follow existing patterns such as:

* `WarehouseController`
* `ActivityLogController`

If one action handles both create and update operations, use Spatie OR syntax:

```php
$this->middleware('permission:module.create|module.edit')->only(['store']);
```

Frontend permission checks are only a UX layer.

Server-side authorization is the source of truth and must never be skipped.

## Sidebar

Every new sidebar menu entry in:

`resources/views/layouts/sidebar.blade.php`

must be protected using:

```blade
@can
```

or:

```blade
@canany
```

with the matching permission.

## Permission Name Stability

Once a permission name has been shipped:

* Never rename it.
* Never repurpose it.
* Never change its meaning.

If a different permission is required, create a new permission instead.

## Global Roles

The global:

* Super Admin
* Business Admin

roles where `business_id = null` are seeder-managed templates.

`PermissionSeeder` synchronizes their permissions through:

`RoleDefaultPermissions`

Do not manually modify these roles for one-off restrictions.

For business-specific restrictions, use a business-scoped custom role created through the Role Create screen.

## HRM & Payroll

These permission rules apply to all HRM and Payroll modules, including:

* Employees
* Departments
* Designations
* Shifts
* Attendance
* Leaves
* Salary Structure
* Payroll
* Payslips
* Employee Advances
* Deductions
* Employee Ledger
* Resignation/Termination
* Clearance
* Asset Allocation
* Employee Self-Service

Employee Self-Service permissions use the existing `ess.*` convention under:

`app/Http/Controllers/Admin/Hrm/`

Use existing HRM permission blocks in `PermissionRegistry.php` as the template.

## Package / Module Gating

Subscription package module gating must use:

```text
module:<key>
```

route-group middleware.

This is handled by:

`App\Http\Middleware\EnsureModuleEnabled`

and:

`FeatureLimitService::hasModule()`

Example:

```text
module:hrm
module:payroll
```

Package-tier checks are separate from action-level `permission:` middleware.

Never place package-tier module checks directly inside controller constructors because constructors can execute during CLI route introspection before an authenticated user exists.

---

# 8. Documentation — MANDATORY

Documentation is part of the implementation and must remain synchronized with the codebase.

The project has two permanent documentation sets:

## Business Documentation

Location:

`resources/docs/business/*.md`

Business documentation must be:

* Business-friendly
* Workflow-oriented
* Free from unnecessary technical jargon

## Developer Documentation

Location:

`resources/docs/developer/*.md`

Developer documentation should cover:

* Architecture
* Database schema
* Routes
* Permissions
* Modules
* Controllers
* Services
* Relationships
* Integrations
* Where functionality exists
* How functionality connects together

Documentation is available through:

`/documentation`

which redirects to:

`admin/documentation`

The documentation system uses:

`App\Http\Controllers\Admin\DocumentationController`

and:

`App\Services\Concrete\Admin\DocumentationService`

## Documentation Manifest

Section order and titles are defined in:

```text
DocumentationService::businessSections()
DocumentationService::developerSections()
```

When adding or modifying documentation:

* Update the manifest where required.
* Add/update the corresponding Markdown file.
* Keep the manifest and Markdown files synchronized.

## Documentation Must Be Updated in the Same Task

Whenever a task adds, changes, or removes any of the following, update the relevant documentation during the same task:

* Feature
* Module
* CRUD
* Database table
* Database column
* Migration
* Route
* API
* Controller
* Service
* Repository
* Model
* Business rule
* Report
* Setting
* Permission
* Integration
* Architecture decision
* Workflow
* User-visible behavior
* Developer-relevant behavior

Do not postpone documentation to a later task.

## Before Implementing

Before changing an area:

1. Check the relevant files in `resources/docs/business/`.
2. Check the relevant files in `resources/docs/developer/`.
3. Understand the existing documentation.
4. Extend or correct it instead of creating duplicate or contradictory documentation.

## Code Is the Source of Truth

Never document functionality that does not actually exist.

If documentation conflicts with the implementation:

1. Verify the actual code.
2. Treat the implementation as the source of truth.
3. Correct the documentation to match the actual implementation.

## PDF Documentation

There is no separate PDF regeneration process.

PDFs are rendered from current Markdown using the existing documentation PDF system.

Keep the Markdown files current.

## New Module Documentation

If a new CRUD/module is added, update:

`resources/docs/developer/03-modules-controllers-services.md`

Also update Business Documentation when the functionality is user-facing.

## Documentation Verification

After changing documentation:

* Verify the `/documentation` portal.
* Verify the affected section exists.
* Verify internal references are valid.
* Verify PDF export still works for the affected documentation.
* Fix broken references or missing sections before considering the task complete.

---

# 9. Frontend Rules

* Follow the existing JavaScript, CSS, HTML, and Vue structure.
* Do not rewrite working frontend code without a valid reason.
* Preserve the existing UI unless a UI change is explicitly requested.
* Reuse existing components.
* Avoid duplicate frontend logic.
* Keep components focused and reasonably sized.
* Preserve responsive behavior.
* Avoid unnecessary direct DOM manipulation when framework state/reactivity can solve the problem.
* Do not introduce unnecessary frontend dependencies.
* Preserve existing assets unless their replacement is explicitly requested.

---

# 10. Git / File Modification Rules

Keep changes focused and easy to review.

* Modify only files required for the task.
* Do not rename files unless necessary.
* Do not move files unless necessary.
* Do not delete files unless clearly obsolete or explicitly requested.
* Do not delete assets without verifying they are unused.
* Do not modify unrelated functionality.
* Do not perform unrelated refactoring while implementing a feature.
* Avoid large rewrites when a targeted change is sufficient.

---

# 11. Editing Rules

Never rewrite an entire file when a small targeted change is enough.

Always:

* Preserve existing formatting.
* Preserve existing indentation.
* Preserve existing coding style.
* Update existing functions when appropriate.
* Reuse existing abstractions.
* Remove code only when clearly obsolete or explicitly requested.
* Avoid introducing new dependencies unless necessary.

---

# 12. Before Implementation

For every task:

1. Inspect the relevant project structure.
2. Read `CLAUDE.md`.
3. Read all relevant existing files.
4. Search for existing implementations of similar functionality.
5. Check existing routes, APIs, services, models, components, permissions, and documentation.
6. Identify reusable code.
7. Determine the smallest clean implementation.
8. Identify risks for complex changes.

For major tasks, provide a concise implementation plan before making substantial changes.

---

# 13. Never Invent Functionality

Do not assume that something exists.

Before using:

* API endpoints
* Routes
* Controllers
* Methods
* Models
* Relationships
* Database columns
* Services
* Repositories
* Permissions
* Components
* Configuration values

verify that they exist in the project.

If something does not exist, determine whether it should actually be created based on the existing architecture.

Never invent business logic simply to make an implementation work.

---

# 14. After Implementation

After completing a major change:

1. Run the appropriate project/build commands.
2. Fix compilation errors.
3. Fix syntax errors.
4. Fix broken imports.
5. Fix missing assets.
6. Fix runtime errors.
7. Check relevant browser console errors.
8. Run relevant tests when available.
9. Verify database migrations where applicable.
10. Verify permissions where applicable.
11. Verify documentation where applicable.
12. Verify that existing functionality was not unnecessarily affected.

Final response should include:

* What was changed.
* Modified files.
* Required migrations/commands.
* Required manual steps.
* Potential side effects or breaking changes.
* Any remaining issues or limitations.

---

# 15. Dependency Rules

Do not add a new package/library unless it is genuinely necessary.

Before adding a dependency:

1. Check whether the project already has an equivalent package.
2. Check whether existing functionality can solve the requirement.
3. Prefer the existing project architecture.
4. Add a dependency only when it provides a clear benefit.

---

# 16. Security

Never expose:

* API keys
* Secrets
* Passwords
* Database credentials
* Private configuration
* Authentication tokens

in frontend source code or public assets.

Never commit secrets into the repository.

Never add arbitrary JavaScript execution through CMS fields.

Never bypass authentication or authorization to make a feature work.

Never expose sensitive ERP/database information through public APIs.

---

# 17. Data Integrity

Existing production/business data must be treated as valuable.

Never:

* Delete existing data without explicit instruction.
* Perform destructive migrations without explicit approval.
* Change existing business records as a side effect of an unrelated feature.
* Change financial/accounting records without understanding the existing business logic.
* Remove historical records simply to simplify implementation.

When a data migration is necessary, make it explicit and reversible where practical.

---

# 18. Priority Order

When deciding how to implement a task, prioritize:

1. Explicit user requirement
2. Security and data integrity
3. `CLAUDE.md`
4. These Cursor project rules
5. Existing project architecture and conventions
6. Simplicity and maintainability
7. Performance optimization where valuable

Never sacrifice correctness merely to complete a task faster.

---

# 19. Final Development Principle

Always prefer:

**Configuration over hard-coding**

**Existing architecture over unnecessary rewrites**

**Reusable code over duplication**

**Shared business logic over duplicated logic**

**Server-side authorization over frontend-only protection**

**ERP/database source of truth over duplicated master data**

**Documentation synchronized with development**

**Small focused changes over large rewrites**

**Correctness over speed**

**Maintainability over quick hacks**

---

# 20. Mandatory Final Check

Before considering any task complete, verify:

* [ ] `CLAUDE.md` was reviewed.
* [ ] Existing implementation was inspected.
* [ ] Existing code was reused where appropriate.
* [ ] No unrelated files were modified.
* [ ] No unnecessary dependencies were added.
* [ ] Existing functionality was preserved.
* [ ] Permissions were added/updated when required.
* [ ] Server-side authorization exists where required.
* [ ] Database migrations were created when required.
* [ ] Documentation was updated when required.
* [ ] Build/tests were run where applicable.
* [ ] Compilation/runtime errors were checked.
* [ ] No secrets or credentials were exposed.
* [ ] No unnecessary refactoring was performed.
