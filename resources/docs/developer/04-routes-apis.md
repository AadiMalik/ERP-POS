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
| `platform:<storefront\|pos\|offline-pos>` | `EnsurePlatformAccess` → the 4 boolean columns on `businesses` | Business Access Control — Super Admin blocking one platform for an entire business, independent of subscription/package. ERP itself is gated separately, at login (`LoginController::attemptLogin()`) and mid-session (`CheckBusinessSubscription`), not via a route middleware. See [Notifications, Alerts & Access Control](23-notifications-alerts.md). |

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

## Public Website Catalog APIs

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/v1/products/{business_id}` | Shop listing + homepage `sections` (page 1, no filters) |
| GET | `/api/v1/website-home/{business_id}` | Aggregated homepage payload (`product_groups.*.enabled` when non-empty) |

Homepage `sections` / `product_groups` keys: `featured_products`,
`discounted_products`, `trending_products`, `new_arrivals`, `best_sellers`
(12 each). Featured / Trending / New Arrivals / Best Sellers fill remaining
slots with other website-visible products when the prioritized set is short.
Discounted Products never fills with non-discounted items — an empty list
means the Vue themes hide that section entirely.

## Mobile App Customer API (`routes/mobile.php`)

Registered in `RouteServiceProvider` with prefix `/api/mobile` (same pattern as
`/api/intro`). Controllers live under `App\Http\Controllers\Api\Mobile\` and
services under `App\Services\Concrete\Api\Mobile\`.

Same endpoint surface as the website storefront (`/api/v1/...`), but on its own
prefix so the mobile app can diverge later without touching the Vue storefront:

| Website (`/api/v1/...`) | Mobile (`/api/mobile/...`) |
|---|---|
| `POST /auth/{check-email, send-otp, …}` | `POST /auth/{check-email, send-otp, …}` |
| `GET /products/{business_id}` | `GET /products/{business_id}` |
| `GET|POST /cart/{business_id}` | `GET|POST /cart/{business_id}` |
| `POST /checkout/{business_id}` | `POST /checkout/{business_id}` |
| … (full mirror of public + Sanctum groups) | … |

Auth OTPs still use the `storefront` email channel (tenant branding). Sanctum
tokens are named `mobile-auth`. Mobile cart/checkout/wishlist/order/account
services currently extend the website Api services so behaviour stays identical;
override only the Mobile subclasses when the app needs different rules.

Because Website and Mobile App share this exact endpoint surface with no
client-identifying signal, Business Access Control's `platform:storefront`
middleware necessarily gates them **together** as one switch — there is
currently no way to block one without the other. Splitting them would require
both frontends to start sending a client-platform header the backend can key
off of.

## Public Intro/Marketing API (`routes/intro.php`)

Registered with prefix `/api/intro`, powering the separate public marketing
site (`erp-intro`, a Vue 3 app in its own repo — see
[Platform Ecosystem](14-platform-ecosystem.md)) that introduces the
ERP/POS/Website/Mobile product to prospective businesses. Controllers live
under `App\Http\Controllers\Api\Intro\`. Unlike `api.php`/`mobile.php`, every
route here is fully public — no Sanctum auth anywhere. The global `api`
throttle (300/min) still applies for GETs; the write endpoints
`business-register`, `contact`, and `blog-comments` also use
`throttle:20,1`. Those three controllers treat a filled honeypot field
`website` as a bot and return a fake success without persisting — Command
Center forms include a hidden `website` input for this:

| Method | Path | Controller@Action | Purpose |
|---|---|---|---|
| GET | `packages`, `packages/{id}` | `PackageController@index/show` | Subscription package catalog (reads the same `packages` table Super Admin manages) |
| POST | `business-register` | `BusinessController@register` | Creates an `intro_business_registrations` row ahead of onboarding a new tenant `Business`. Accepts text fields (`package_id`, `business_name`, `owner_name`, `owner_email`, `owner_phone`, `business_type`, `city`, `address`, `notes`, `billing_cycle`, `payment_reference`) plus optional `payment_proof` file (`jpg,jpeg,png,pdf`, max 5MB) stored on the pending subscription payment — see [Platform Ecosystem](14-platform-ecosystem.md) |
| GET | `modules`, `modules/{slug}` | `ModuleController@index/show` | Marketing copy describing platform modules |
| GET | `blogs`, `blogs/{slug}`, `blog-categories`, `blog-tags` | `BlogController@*` | Blog content |
| POST | `blog-comments` | `BlogCommentController@store` | Blog comment submission |
| GET | `testimonials` | `TestimonialController@index` | Customer testimonials |
| POST | `contact` | `ContactController@store` | General inquiry form |
| GET | `website-settings` | `WebsiteSettingController@show` | Platform-level site identity/branding — distinct from a tenant's own `website-settings/{business_id}` above |
| GET | `navigation` | `NavigationController@index` | Header/footer nav tree |
| GET | `pages`, `pages/{slug}` | `PageController@index/show` | Static CMS pages |
| GET | `homepage` | `HomepageController@show` | Aggregated homepage payload |

All content here is managed through the **Intro CMS** admin screens, kept
deliberately separate from a tenant's own Website CMS since it represents the
platform, not any one business.

## Offline Desktop POS API (`routes/offline.php`)

Registered in `RouteServiceProvider` with prefix `/api/offline`. Powers the
**Electron + Vue 3** desktop POS client in the separate **`erp-desktop-pos`**
repository (`C:\xampp\htdocs\erp-desktop-pos` — not inside this ERP repo).
Controllers live under `App\Http\Controllers\Api\Offline\` and services under
`App\Services\Concrete\Api\Offline\`.

The web POS (`/admin/pos-screen`) is unchanged — the desktop app is an
additional offline-first client that reuses `OrderService`,
`PosRegisterSessionService`, and related Admin services on push/sync.

| Method | Path | Auth | Purpose |
|---|---|---|---|
| POST | `/api/offline/auth/ping` | — | Reachability check |
| POST | `/api/offline/auth/login` | — | Staff login → Sanctum token + permission map + bcrypt hash for offline cache |
| POST | `/api/offline/device/register` | Sanctum + `module:pos` | Register installation (`pos_devices` table) → device ID + plain token (stored hashed) |
| GET | `/api/offline/device/info` | Sanctum + device headers | Current device metadata |
| GET | `/api/offline/sync/bootstrap` | Sanctum + device + `module:pos` | Full initial download (settings, catalog, stock, users, registers, …) |
| POST | `/api/offline/sync/pull` | Sanctum + device + `module:pos` | Incremental pull via per-entity `date_updated` cursors |
| POST | `/api/offline/sync/push` | Sanctum + device + `module:pos` | Batch push of queued offline transactions (idempotent) |
| GET | `/api/offline/sync/health` | Sanctum + device + `module:pos` | Online/health probe |
| POST | `/api/offline/orders`, `/complete`, `/hold` | Sanctum + device + permissions | Direct order endpoints (also used by push worker) |
| POST | `/api/offline/register-sessions/{open,close,cash-movement}` | Sanctum + device + permissions | Register session lifecycle |
| POST | `/api/offline/customers` | Sanctum + device + `order.customer.change` | Quick customer create from desktop POS |
| GET | `/api/offline/stock/levels` | Sanctum + device | Warehouse stock snapshot |

Device auth uses headers `X-Pos-Device-Id` and `X-Pos-Device-Token` (middleware
`offline.pos.device` → `EnsureOfflinePosDevice`). Orders pushed from desktop
include `client_request_id` / `offline_local_id` for idempotency and
`pos_device_id` for multi-device tracing.

**Migration required:** `2026_09_02_100000_create_pos_devices_table.php` (also
adds `pos_device_id` / `offline_local_id` columns on `orders`,
`pos_register_sessions`, and `pos_register_cash_movements`).

**Setup flow (desktop client):** Step 1 lock `business_id` and download staff +
locations via `POST /api/offline/setup/bootstrap-business` (saves users with
password hashes to local SQLite) → Step 2 **local** staff login against SQLite →
Step 3 branch/warehouse/register from local data → Step 4
`POST /api/offline/setup/register-device` (staff credentials + location; returns
device token + auth token) → bootstrap sync.

Legacy authenticated endpoints remain: `POST /api/offline/setup/validate-business`,
`GET /api/offline/setup/location-options`, `POST /api/offline/device/register`.

**Local SQLite path (Windows):** `%APPDATA%\ERP Desktop POS\pos.sqlite` — exposed
in the desktop app via `app:get-database-path` IPC.
