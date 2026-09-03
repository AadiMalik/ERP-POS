# Platform Ecosystem: Companion Repos & Their API Contracts

This ERP backend is consumed by separate frontend/client repositories, each on
its own tech stack, its own repo, and (for the Vue/web apps) its own
deployment. This page maps the ecosystem and states where each client's
contract with this backend actually lives in code — useful whenever a change
here needs to be checked against a consumer, or vice versa.

## The Five Repos

| Repo | Path | Stack | Talks to |
|---|---|---|---|
| ERP + POS (this repo) | `c:\xampp\htdocs\erp` | Laravel 8 | — (the backend) |
| Offline desktop POS | `c:\xampp\htdocs\erp-desktop-pos` | Electron + Vue 3 + SQLite | `routes/offline.php` (`/api/offline/...`) |
| Per-business storefront | `c:\xampp\htdocs\smart-mart` | Vue 3 + Vite | `routes/api.php` (`/api/v1/...`) |
| Public intro / sign-up site | `c:\xampp\htdocs\erp-intro` | Vue 3 + Vite | `routes/intro.php` (`/api/intro/...`) |
| Customer mobile app | `D:\smart_mart_mobile` | Flutter (Dio) | `routes/mobile.php` (`/api/mobile/...`) |

One ERP backend, one shared database — each client is a different *view* onto
the same tenant data, never a separate system with its own copy.

## smart-mart & the Mobile App Are Deliberately Identical Contracts

`routes/api.php` and `routes/mobile.php` expose the same endpoint surface
under different prefixes (see [Routes & APIs](04-routes-apis.md)), and on the
PHP side the Mobile API service classes
(`app/Services/Concrete/Api/Mobile/*`) are thin `extends`/delegators over the
same storefront services the Vue app's controllers call
(`app/Services/Concrete/Api/*`, `app/Services/Concrete/Admin/*` for
catalog/CMS). **There is no independent pricing, cart, voucher, or checkout
logic for the mobile app** — if you change a rule in a storefront service, it
applies to the mobile app automatically. Only override a `Mobile*` subclass
when the app genuinely needs different behavior from the website.

Both clients' service/store layers expect the exact envelope
`App\Traits\ResponseAPI` emits: capitalized `{Success, Message, Data}` keys
on every response — not Laravel's typical lowercase convention. Keep this in
mind before hand-writing a raw `response()->json([...])` anywhere on these
routes; it must go through `ResponseAPI` (or match its shape) or every
frontend `unwrap()`/`ApiResult` parser silently treats it as a failure.

**Product reviews:** `ProductReviewService::submit($business_id, …)` verifies
the `product_id` belongs to that route `business_id` before creating a row, so
a customer cannot attach a review to another tenant's catalog via a crafted
request.

A few endpoint-specific contract quirks worth knowing before you touch either
side:

- **Order response fields are deliberately mixed-case.**
  `CustomerOrderService::formatOrder()` returns order-level fields as
  camelCase (`orderNumber`, `paymentStatus`, `placedAt`) but item-level
  fields as snake_case (`product_variation_id`). Both Vue and Flutter clients
  mirror this exactly — it looks inconsistent but is intentional and matched
  on both ends. Don't "fix" one side without the other.
- **The address endpoints accept both casings.** `ProfileController::store
  Address()` validates `fullName|full_name` and `isDefault|is_default` as
  accepted alternates specifically so either client convention works.
- **Product listings always include `badges`.**
  `ProductService::formatCustomerProduct()` (used by both the storefront and
  mobile catalog services) always sets `'badges' => $badges`, even as an
  empty array — never omits the key. The smart-mart `mapProduct()` also
  defaults missing `badges` to `[]` so older payloads cannot crash the UI.
- **Mobile homepage uses the aggregated payload.** Flutter
  `HomeService.fetch()` hits `/mobile/website-home/{business_id}` and renders
  `promo_banners`, hero secondary CTA, and product-rail "See all" into Shop.
  Catalog screens (`Home` / `Shop` / categories / listing) surface
  `ApiResult` failures with retry instead of silent empty lists.

## Dukanaz Command Center Is a Different Kind of Client

Unlike the storefront/mobile pair, `routes/intro.php` is **entirely public**
— no Sanctum auth on any route — because it represents the platform itself,
not a tenant business. GETs rely on the global `api` throttle (300/min);
`business-register`, `contact`, and `blog-comments` add `throttle:20,1` plus
a `website` honeypot (filled → fake success, no persist). It's powered by
`App\Http\Controllers\Api\Intro\*` and reads/writes: platform-level
`packages`, `modules`, `blog_*`, `testimonials`, `website_settings` (the
platform's own, distinct from a tenant's `website_settings`), and the
`intro_business_registrations` table created by `BusinessController::register()`
ahead of onboarding a new tenant `Business`.

**Intro business registration accepts a payment proof:**
`Api\Intro\BusinessController::register()` validates an optional
`payment_proof` file (`jpg,jpeg,png,pdf`, max 5MB) and stores it under
`public/uploads/subscription_payments/`. The filename is passed through
`BusinessRegistrationService::registerFromIntro()` →
`SubscriptionService::createInitial()` → `PaymentService::record()` onto the
pending `subscription_payments` row (same path as My Subscription uploads).
When a proof is present the new business is set to `under_review`. The Command
Center registration UI must submit `FormData` (not JSON) so the file reaches
this endpoint.

## Where to Look When a Cross-Repo Bug Is Reported

1. Confirm which prefix the client actually hit — `/api/v1/...` (storefront),
   `/api/mobile/...` (Flutter), or `/api/intro/...` (Command Center) — they
   are not interchangeable even where paths look similar.
2. For storefront/mobile, check the shared service first
   (`app/Services/Concrete/Api/*` or `Admin/*`) before assuming the bug is
   route-specific — a fix there fixes both clients.
3. Diff the client's expected request/response field names against the
   relevant `Service::format*()`/`Resource` method directly in this repo —
   don't assume the frontend's assumption is correct or that the backend's
   convention is a bug; check [Routes & APIs](04-routes-apis.md) and this
   page for the quirks already known to be intentional.
