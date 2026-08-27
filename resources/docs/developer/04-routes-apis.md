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

## Web Auth (guest)

`Auth::routes(['register' => false])` plus OTP reset routes in `routes/web.php`.
Admin forgot-password is **not** Laravel's email-link broker — it uses the same
`OtpService` / `otps` table as the customer API:

| Method | Path | Name | Controller |
|---|---|---|---|
| GET | `/login` | `login` | `LoginController` |
| GET | `/password/reset` | `password.request` | `ForgotPasswordController@showLinkRequestForm` |
| POST | `/password/email` | `password.email` | `ForgotPasswordController@sendResetLinkEmail` |
| GET | `/password/otp` | `password.otp` | `ForgotPasswordController@showOtpForm` |
| POST | `/password/otp` | `password.otp.reset` | `ForgotPasswordController@resetWithOtp` |
| POST | `/password/otp/resend` | `password.otp.resend` | `ForgotPasswordController@resendOtp` |

These views use `layouts.auth` (no sidebar). A leftover `/password/reset/{token}`
bookmark is redirected to `password.request`. Send/resend/reset are throttled
`6,1`. Unregistered (or inactive/deleted) emails get
“This email is not registered.” and stay on the form — no OTP is sent.
`OtpService::send()` emails a branded Dukanaz HTML+text template
(`emails.otp`) via the user's `business_id` EmailSetting and
falls back to the platform (`business_id = null`) channel for Super Admin or
tenants without SMTP.

## Public / Customer-Facing API (`routes/api.php`)

Minimal — not part of the Admin surface. Powers the customer mobile app/website
identity flow via `App\Http\Controllers\Api\Auth\AuthController`:
- `GET /api/user` (Sanctum) — current authenticated user.
- `POST /api/v1/auth/{check-email, send-otp, resend-otp, verify-otp,
  login-password, forgot-password, reset-password}` — throttled `20,1`.
- `POST /api/v1/auth/{set-password, logout}` (Sanctum-protected).

This is a shared email+OTP identity API, not a general-purpose REST API over the
ERP's business data — there is no public `/api/v1/products`, `/api/v1/orders`, etc.
Onboarding/login creates or ensures a `CustomerProfile` via
`CustomerAccountService::ensureProfile()` → `CustomerService::upsertProfile()`,
which attaches `accounting_settings.default_customer_account_id` when configured.

Website auth OTPs (`send-otp` / `resend-otp` for onboarding or login, and
`forgot-password`) all call `OtpService::send(..., 'storefront')` —
business logo/name/theme colors with a Powered by Dukanaz footer
(`emails.otp-storefront`). `POST /api/v1/auth/forgot-password` requires `email`
and `business_id`, checks `CustomerAccountService::emailExistsForBusiness()`,
and returns “This email is not registered.” when there is no customer profile
for that business (no mail sent).

## Public Website Settings & Theme APIs

Consumed by the Vue storefront (`frontend_design`) at bootstrap:

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/v1/website-settings/{business_id}` | Business identity, currency, SEO, favicon, hours, WhatsApp, free delivery, bank details |
| GET | `/api/v1/website-theme/{business_id}` | Theme preset, colors, typography, button styles |

`website-settings.favicon` is the business upload URL when set; otherwise the
platform Dukanaz `favicon-32.png` asset URL so the storefront tab icon never
falls back to a placeholder emoji.
