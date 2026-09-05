# Payment Gateway Framework

Provider-based online payment integration for **Website and Mobile App checkout
only** - never POS, which keeps its own cash/card/bank/credit/wallet payment
architecture completely independent (see `03-modules-controllers-services.md`
for POS's `OrderService`).

## Architecture

**Provider catalog is code, not a DB table.**
`App\Services\PaymentGateways\PaymentGatewayProviderRegistry::providers()` is
the static list of every known provider (`jazzcash`, `stripe`, `easypaisa`,
`payfast`, `safepay`, `nayapay`, `sadapay`, `paypal`, plus the test-only
`dummy_test`), each entry giving: label, adapter class, supported
countries/currencies/payment-method codes, `supports_refund`/
`supports_webhook` flags, and the config fields it needs in Sandbox vs Live
mode. This is what makes "add a gateway" a one-adapter-class-and-one-registry-
entry change - see **Adding a new provider** below.

**`payment_gateways` table** - a business may keep **more than one row per
provider** (e.g. rotating Stripe credentials), but at most one row per
`(business_id, provider_code)` may ever be `is_active = 1` at a time -
enforced in `PaymentGatewayService`, not by a DB constraint (the DB can't
express "unique among active rows only"): `save()` refuses to create a new
row for a provider while one is already active, and `status()` refuses to
activate a row while another is active - either way the existing active one
must be deactivated (or deleted) first
(`2026_09_05_150000_drop_unique_constraint_on_payment_gateways_provider`
replaced the original hard unique index with a plain one once this became a
business rule rather than a schema rule). Key columns: `is_active`,
`website_enabled`/`mobile_enabled`, `active_mode` (`sandbox`|`live`), and two
independent encrypted config blobs - `config_sandbox`/`config_live`
(`App\Models\PaymentGateway` casts both `encrypted:array`, the same mechanism
`FirebaseSetting::private_key` already uses for its one field) - so
switching modes never loses the other mode's credentials.

**Linked `payment_methods` row.** `payment_methods.type` gained a `gateway`
value plus a nullable `payment_gateway_id` FK
(`2026_09_05_130100_add_gateway_type_to_payment_methods_table`).
`PaymentGatewayService::ensureLinkedPaymentMethod()` auto-creates/syncs one
`payment_methods` row per gateway (`is_website_only = 1`, so
`PaymentMethodService::getAllActive()` - the POS tender list - already
excludes it via the exact same flag COD uses; no new POS-exclusion logic was
needed). This is how a gateway payment plugs into the existing
`order_payments`/accounting pipeline unchanged.

**`payment_transactions` table** - the full lifecycle record. One row is
created at "initiate" time and doubles as the payment session (no separate
session table - Website/Mobile checkout is and stays single-payment-per-order,
matching the pre-existing architecture). Status enum: `initiated`, `pending`,
`processing`, `authorized`, `paid`, `failed`, `cancelled`, `expired`,
`refunded`, `partially_refunded`, `disputed`, `unknown`
(`App\Models\PaymentTransaction::TERMINAL_STATUSES` lists the ones after
which a transaction never changes again). `meta` only ever holds safe,
non-secret reference fields - never card numbers/CVV/credentials.

**`payment_gateway_webhook_logs` table** - replay/duplicate-event protection,
unique on `(provider_code, event_id)`, independent of whether the event maps
to a known transaction.

**Provider contract** (`App\Contracts\PaymentGatewayProviderContract`):
`initiate()`, `verify()`, `handleWebhook()`, `refund()`, `cancel()`,
`supportsRefund()`, `supportsWebhook()`. Every method but `initiate()`
(whose return shape genuinely varies by provider - redirect form fields vs a
client_secret) returns one shared DTO,
`App\Services\PaymentGateways\Support\PaymentGatewayResult`.
`App\Services\PaymentGateways\PaymentGatewayManager::adapterFor()` resolves a
`PaymentGateway` row to its adapter via the registry - controllers/services
never talk to a specific provider SDK directly.

## Provider implementation status

| Provider | Adapter | Status |
|---|---|---|
| JazzCash | `Providers\JazzCashProvider` | Real (HashRequest MWALLET API, coded from public docs) |
| Stripe | `Providers\StripeProvider` | Real (PaymentIntents REST API via `Illuminate\Http`, no SDK dependency added) |
| PayPal | `Providers\PayPalProvider` | Real (official Orders v2 REST API - highest confidence of the six, extensively documented) |
| Safepay | `Providers\SafepayProvider` | Real (public checkout-session REST API per docs.getsafepay.com) |
| Easypaisa | `Providers\EasypaisaProvider` | Real (EasyPay hosted-checkout hash-form API, widely-published merchant guide) |
| PayFast (PK) | `Providers\PayFastProvider` | Real (PostTransaction hosted-checkout REST API - lower confidence, PayFast PK's public docs have varied across versions; confirm field names against your merchant pack) |
| NayaPay, SadaPay | `Providers\*Provider` (extend `StubProvider`) | Registered, config fields defined, visible in the CMS - deliberately left as stubs: no public merchant/business API documentation was available to implement them correctly without inventing an API shape. Every operation throws until a real adapter is implemented |
| `dummy_test` | `Providers\DummyTestProvider` | Test-only fake gateway backing the automated suite; hidden from the CMS (`PaymentGatewayProviderRegistry::forSelect()` filters `internal` entries) |

None of the six real adapters has been exercised against a live sandbox - no
test credentials were available at implementation time. All are coded
faithfully to each provider's official/publicly-documented API and security
scheme (JazzCash/Easypaisa/PayFast: sorted-fields HMAC hash; Stripe/Safepay:
signed-header HMAC; PayPal: its own server-side signature-verification
endpoint) and covered by `tests/Feature/PaymentGateway/PaymentLifecycleTest.php`
against the dummy provider, which implements the exact same contract. Treat
all six as "implementation complete, sandbox-unverified" until real
credentials are supplied and a manual end-to-end Sandbox run is done -
PayFast's confidence is notably lower than the other five since its public
documentation is less consistent across versions.

Google Pay / Apple Pay and PayPak are **not** separate gateways - they are
declared in `supported_payment_methods` under a capable gateway (Stripe;
later a card-network gateway like PayFast for PayPak), matching the task's
"payment method/network, not a gateway" requirement.

## Payment lifecycle

1. **Website/Mobile checkout** (`app/Services/Concrete/Api/WebsiteCheckoutService.php`,
   shared by mobile via `MobileCheckoutService extends WebsiteCheckoutService`)
   creates a `hold` order exactly as before, now also accepting a
   `payment_methods.type === 'gateway'` selection (widened
   `resolveWebsitePaymentMethod()` - the only edit made to existing checkout
   code). `paid_amount` stays 0, same as COD/Bank today.
2. Frontend calls `POST orders/{business_id}/{order_id}/pay` with the chosen
   `payment_gateway_id` → `App\Services\Concrete\Api\PaymentService::initiate()`
   creates a `payment_transactions` row (`status = initiated` → `pending`
   after the adapter's `initiate()` call) and returns whatever the provider's
   client flow needs (JazzCash: an auto-submit form; Stripe: a
   `client_secret`).
3. Frontend completes the provider's flow, then either:
   - **Webhook** (authoritative): the provider calls
     `POST /api/webhooks/payment-gateways/{business_id}/{provider_code}` →
     `PaymentGatewayWebhookController` → `PaymentService::handleWebhook()`,
     which resolves the currently **active** gateway row for that pair (see
     the note on this route below - resolving by business/provider rather
     than `payment_gateway_id` is deliberate).
   - **API poll**: frontend calls `POST payments/{business_id}/{transaction_id}/verify`
     → `PaymentService::verify()`, which calls the adapter's `verify()`
     directly with the provider - never trusts the frontend's own "success"
     redirect.
4. `PaymentService::applyResult()` is the single point every outcome passes
   through: amount/currency are checked against the authoritative
   transaction (mismatch → `disputed`, order never touched); on a genuine
   `paid` result it calls
   `WebsiteCheckoutService::applyGatewayPaymentSuccess()`, which shares one
   `markOrderPaid()` implementation with the pre-existing manual bank-transfer
   admin-confirm flow (`confirmPayment()`) - accounting/order-sync logic is
   written once.

**Idempotency / duplicate-payment prevention:**
- `payment_transactions.internal_reference` is server-generated and unique -
  never client-supplied, so a session can't be redirected onto a different
  order.
- Every state-changing call locks the transaction row
  (`lockForUpdate()`) and no-ops once it's already in a
  `PaymentTransaction::TERMINAL_STATUSES` state.
- `PaymentService::initiate()` refuses to start a new payment if the order is
  already paid, or already has a `paid`/`authorized` transaction.
- `payment_gateway_webhook_logs` unique `(provider_code, event_id)` hard-blocks
  exact replay of the same provider event, independent of transaction state.
- `markOrderPaid()` itself is idempotent (a no-op once `paid_amount >= total`),
  so even a duplicate "paid" result from two different transactions on the
  same order can never double-credit it.

## Security model

- Secrets encrypted at rest via Laravel's native `encrypted:array` cast
  (AES-256, `APP_KEY`-backed) - `config_sandbox`/`config_live` are also
  `$hidden` on the model.
- The CMS edit screen never receives decrypted secrets -
  `PaymentGateway::maskedConfig(mode)` returns only `field => bool(present)`.
  A field is only overwritten if a new non-empty value is submitted
  (`PaymentGatewayService::mergeConfig()`), mirroring `FirebaseSetting`'s
  `hasPrivateKey()` guard.
- Webhook signature verification is mandatory per-adapter (JazzCash SecureHash
  HMAC-SHA256 / Stripe `Stripe-Signature` HMAC with a 5-minute timestamp
  tolerance) - a failed check throws
  `App\Exceptions\PaymentGateways\InvalidWebhookSignatureException`, is logged
  as `invalid`, and is never processed.
- The webhook route (`routes/api.php`) carries no Sanctum auth (the caller is
  the provider) and needs no CSRF exemption - it's loaded under the `api`
  middleware group, which never applies `VerifyCsrfToken` (see
  `App\Http\Kernel::$middlewareGroups`).
- No card numbers, CVV, or other prohibited card data are ever stored - only
  safe reference fields in `meta`.

## Refunds

Only for gateways whose registry entry sets `supports_refund => true`.
`OrderReturnService::applyOrderReturnPosting()` gained one branch: when the
chosen `refund_payment_method_id` resolves to a `type = 'gateway'` method,
`OrderReturnService::processGatewayRefund()` looks up the original `paid`
`payment_transactions` row for that order/gateway and calls
`PaymentTransactionService::refundTransaction()` (the same method the CMS's
Payment Transactions screen uses) **inside the same DB transaction** as the
return's approval, so a failed gateway refund rolls back the whole approval
rather than leaving accounting and the real gateway state out of sync. A new
`payment_transactions` row is recorded for the refund itself
(`refund_of_transaction_id` set); the *original* transaction's own status
becomes `refunded`/`partially_refunded` based on cumulative `refunded_amount`.

## Sandbox / Live setup

Every gateway keeps two fully independent credential sets
(`config_sandbox`/`config_live`) and one `active_mode` switch. Toggling
`active_mode` never discards the other mode's configuration - a business can
finish configuring Live while Sandbox stays intact for later testing. Give
the provider your webhook URL as
`{APP_URL}/api/webhooks/payment-gateways/{business_id}/{provider_code}`.

This resolves by `(business_id, provider_code)` rather than
`payment_gateway_id` specifically to avoid a chicken-and-egg problem: most
providers (Stripe included) require a signing secret you can only get *after*
creating a webhook on their own dashboard, which needs our URL - but our URL
would otherwise need a `payment_gateway_id` that only exists *after* the
gateway row is saved. Since both `business_id` and `provider_code` are known
before the row is ever saved (chosen right there on the Create screen), the
CMS shows this URL immediately - fill in the webhook secret on a second save
once the provider hands it to you. The URL never changes for that pair
afterward, and at delivery time it always targets whichever row is currently
the *active* one for that business/provider (see the "only one active row
per provider" rule in the `payment_gateways` table section above) -
reactivate a different row later and the same URL routes to it
automatically, no re-registration needed on the provider's side.

## CMS

- **Admin → Payment Gateways** (`payment-gateway.*` permissions,
  `App\Http\Controllers\Admin\PaymentGatewayController`): CRUD, dynamic
  per-provider config form (`PaymentGatewayProviderRegistry` drives field
  rendering in `resources/views/admin/payment-gateway/create.blade.php`),
  activate/deactivate, "Test Connection" (calls the adapter's
  `App\Contracts\PaymentGatewayConnectionTestable::testConnection()` when
  implemented - Stripe does a safe `GET /v1/balance`; JazzCash has no safe
  ping endpoint, so the service falls back to a config-completeness check).
- **Admin → Payment Gateway Transactions** (`payment-transaction.*`
  permissions, `App\Http\Controllers\Admin\PaymentTransactionController`):
  filterable listing (business/order/gateway/environment/method/status/date
  range/reference/amount/currency) + manual refund action. Clearly separate
  from POS's own payment/order reports.
- Both are gated behind the `module:payment-gateway` route-group middleware
  and a `payment-gateway` entry in `SubscriptionModuleRegistry`
  (`default_enabled => true`, so no existing business loses access).

## API surface (Website `routes/api.php` / Mobile `routes/mobile.php` - identical shape)

- `GET payment-gateways/{business_id}` - public, active/configured gateways
  for this business + platform (mirrors the existing
  `GET payment-methods/{business_id}`).
- `POST orders/{business_id}/{order_id}/pay` - `auth:sanctum`, starts a
  payment against an already-created hold order.
- `GET payments/{business_id}/{payment_transaction_id}` - cheap status poll,
  no provider call.
- `POST payments/{business_id}/{payment_transaction_id}/verify` - `auth:sanctum`,
  server-to-server re-check with the provider.
- `POST webhooks/payment-gateways/{business_id}/{provider_code}` (public,
  `routes/api.php` only - shared by both platforms; resolves to whichever
  gateway row is currently active for that pair, not a specific row id - see
  "Sandbox / Live setup" above for why).

Mobile's `Api\Mobile\PaymentController extends Api\PaymentController` with
only `$platform = 'mobile'` overridden - no service subclass, mirroring how
`MobileCheckoutService` needed no overrides either.

## Adding a new provider

1. Implement `App\Contracts\PaymentGatewayProviderContract` in
   `app/Services/PaymentGateways/Providers/<Name>Provider.php` (implement
   `App\Contracts\PaymentGatewayConnectionTestable` too if the provider has a
   safe, side-effect-free credential check).
2. Add one entry to
   `App\Services\PaymentGateways\PaymentGatewayProviderRegistry::providers()`:
   label, adapter class, countries/currencies/payment-method codes,
   `supports_refund`/`supports_webhook`, and `config_fields` per mode.
3. Nothing else changes - no checkout/webhook/refund logic branches on
   provider identity anywhere in the codebase.
