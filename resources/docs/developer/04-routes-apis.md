# Routes & APIs

## Admin Route Conventions

Almost every list screen follows the same shape: `Route::resource(...)` for
CRUD, plus a sibling `POST .../data` route (feeding a server-side DataTable via the
Service's `getData()`), and where relevant `status`/`import`/`export` actions.
Report controllers additionally follow a uniform `index / data / print / pdf /
export / export-csv` action set (see
[Reports Infrastructure](06-reports-infrastructure.md)).

## Middleware Gating

All admin routes sit inside one group requiring `auth`, `check.subscription`,
`setting`, and `must-change-password`. On top of that:

| Middleware | Resolves via | Gates |
|---|---|---|
| `module:<key>` | `EnsureModuleEnabled` → `FeatureLimitService::hasModule()` → `SubscriptionModuleRegistry` + the tenant's `Business->package->modules` | Whole module umbrellas: `hrm`, `payroll`, `inventory`, `accounting`, `pos`, `service-management` (each independently toggleable per subscription package) |
| `permission:<name>` | Spatie's permission middleware, backed by `PermissionRegistry` | Individual actions — see [Permissions & Access Control](05-permissions-access-control.md) |
| `superadmin` | `EnsureSuperAdmin` | Platform-level SaaS billing/subscription admin screens only |

Core keys (`dashboard`, `permission`, `role`, `package`, `business`, `subscription`,
`my-subscription`, `setting`, `activity-log`, `login-history`, `notification`,
`documentation`, `reports`, plus `branch`/`user`) are **never** gated by `module:`
middleware — always available regardless of subscription package, gated only by
permission.

`EnsureModuleEnabled` is registered as a real HTTP middleware (not inline
controller code) specifically so it's safe to run outside an HTTP request context
(e.g. during `route:list`), and Super Admin always passes every module check.

## Adding a New Route Group

Follow an existing group in `routes/web.php` as the template (e.g. the
`documentation` group added alongside `setting`). If the new screen belongs to a
subscription-gated module, wrap it in that module's existing `module:<key>` group
rather than inventing a new one unless it's genuinely a new toggleable feature (see
[Subscription & Module Gating](08-subscription-module-gating.md)).

## Public / Customer-Facing API (`routes/api.php`)

Minimal — not part of the Admin surface. Powers the customer mobile app/website
identity flow via `App\Http\Controllers\Api\Auth\AuthController`:
- `GET /api/user` (Sanctum) — current authenticated user.
- `POST /api/v1/auth/{check-email, send-otp, resend-otp, verify-otp,
  login-password, forgot-password, reset-password}` — throttled `20,1`.
- `POST /api/v1/auth/{set-password, logout}` (Sanctum-protected).

This is a shared email+OTP identity API, not a general-purpose REST API over the
ERP's business data — there is no public `/api/v1/products`, `/api/v1/orders`, etc.
