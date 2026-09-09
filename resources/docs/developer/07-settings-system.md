# Settings System

## Shape

There is **no single generic `Setting` model/config-in-db table**. Instead, each
settings domain is its own Eloquent model — `BusinessSetting`, `AccountingSetting`,
`CustomerSetting`, `SupplierSetting`, `InventorySetting`, `EmailSetting`,
`SmsSetting`, `WhatsappSetting`, `FirebaseSetting`, `FbrSetting`, `PraSetting`, `PrintSetting`,
`BarcodeSetting`, `ThemeSetting`, `ThermalPrintSetting`, `NotificationSetting`,
`PosSetting` — each presumably one row per business. `Business` exposes a `hasOne`
relation to every one of them.

Two settings domains are scoped to a **branch** rather than the whole business:
`ThermalPrintSetting` (optional per-branch override, `branch_id` nullable — falls
back to the business default row when a branch has none of its own; see "Print
Configuration" below) and `BranchTaxSetting` (mandatory per-branch, `branch_id`
NOT NULL/unique — every branch always has exactly one row, auto-created on first
read; see "Per-Branch Tax Configuration" below). `Branch` exposes a `hasOne
BranchTaxSetting` relation for this one, not `Business`.

## Controller/Service

`App\Http\Controllers\Admin\SettingController` (gated by a single
`$this->middleware('permission:setting.manage');` since every section shares one
permission) delegates to `App\Services\Concrete\Admin\SettingService`, which has
one `updateXxxSetting()` method per domain — `routes/web.php`'s `setting` group
maps one `POST` route per section to its update method.

Every method (index and every `updateXxxSetting()`) resolves its target
business via `SettingController::resolveTargetBusinessId(Request $request)`
rather than reading `Auth::user()->business_id`/`$request->business_id`
directly: a Super Admin passing `business_id` (query param on `index`, form
field on every save) targets that business; everyone else is always pinned to
their own `business_id` regardless of what they send. This is both the
mechanism behind the Super Admin business-selector dropdown on the Settings
screen (`resources/views/admin/setting/index.blade.php` - `#settingsBusinessSelect`,
reloads with `?business_id=`, and every AJAX save picks the same value back up
via `currentSettingsBusinessId()`/`buildSettingFormData()`) and the fix for a
pre-existing IDOR where any authenticated user holding `setting.manage` could
overwrite another tenant's settings by forging `business_id` in the POST body.

## Settings Consumed Outside the Settings Screen

Most of these rows are read only by `SettingController`/`SettingService`
itself, but some are consumed elsewhere as real business-rule gates:
- `BusinessSetting.datatable_pagination_position` (`bottom` default, or
  `top` / `both`) is read into `session('business_setting')` and applied by
  `window.erpDtLayout()` so every list DataTable shows page numbers where
  the business chose. See
  [Centralized DataTable System](24-datatable-system.md).
- `InventorySetting.negative_stock` ("Negative Stock") is read by
  `OrderService::allowsNegativeStock()` and gates every stock check in the
  POS sales flow (`OrderService::saveLinesAndComputeTotals()`,
  `post()`, `revalidateStockOnResume()`) - off (default) hard-blocks
  adding/holding/posting more of a tracked product than
  `ProductVariationStock.quantity` allows; on, POS behaves as if stock is
  unlimited. See the Sales & POS section of
  [Modules, Controllers & Services](03-modules-controllers-services.md) and
  the Business docs' "Stock Availability in POS" section
  (`resources/docs/business/03-sales-pos.md`).
- `AccountingSetting` COA defaults include Fixed Asset mappings
  (`default_fixed_asset_account_id`, `default_accumulated_depreciation_account_id`,
  `default_depreciation_expense_account_id`,
  `default_gain_on_asset_disposal_account_id`,
  `default_loss_on_asset_disposal_account_id`) used by
  `FixedAssetAccountingService` for acquisition, depreciation, and disposal JVs.
  Populated automatically for every new business by the Accounting Setup
  Wizard — see below.

## Accounting Setup Wizard (Automatic Business Provisioning)

A non-accountant business owner never has to build a Chart of Accounts or map
default accounts by hand. `App\Services\Concrete\Admin\AccountingSetupWizardService::setupForBusiness($business_id)`
runs automatically, inside the same `DB::transaction()` as `Business::create()`,
from both business-creation paths:
- `App\Services\Concrete\Admin\BusinessService::save()` (admin-created business)
- `App\Services\Concrete\Admin\Intro\BusinessRegistrationService::registerFromIntro()`
  (public self-registration)

It composes two lower-level services:
1. `ChartOfAccountsCloneService::cloneTemplateToBusiness($business_id)` — deep
   clones the system-level Chart of Accounts template (`AccountType` →
   `AccountSubType` → parent `Account` → child `Account`, any depth, all rows
   with `business_id = NULL`) into brand-new rows owned by the business,
   covering Cash, Bank, Accounts Receivable/Payable, Inventory, COGS, Sales,
   Purchases, Expenses, Fixed Assets, Accumulated Depreciation, Depreciation
   Expense, and Gain/Loss on Asset Disposal. Returns a
   `template_account_id => new_account_id` map.
2. `AccountingSettingCloneService::cloneTemplateToBusiness($business_id, $accountIdMap)` —
   copies the template `AccountingSetting` row (`business_id = NULL`) into a
   new row for the business, remapping every `default_*_account_id` field
   through that map. A field is left `null` (never a global/template id) if
   its template account wasn't cloned.

The system-level template itself — both the account tree and which account
each `default_*_account_id` field points at — is seeded by
`database\seeders\ChartOfAccountsTemplateSeeder` (`php artisan db:seed --class=ChartOfAccountsTemplateSeeder`)
and is editable afterwards by Super Admin through the same Settings >
Accounting screen (Super Admin's own `business_id` is `NULL`). Account ids are
never hard-coded anywhere in this pipeline — the seeder resolves accounts by
their stable `code`, and the clone services resolve everything dynamically
per business.

**Idempotent by design** — both clone services can be called again for a
business that was already provisioned without creating duplicates:
`ChartOfAccountsCloneService` matches an existing type/sub-type by `name` and
an existing account by `code` before cloning a new one, and
`AccountingSettingCloneService` reuses an existing `AccountingSetting` row and
only fills in fields that are still `null` — a mapping an accountant/admin has
since changed via Settings > Accounting is never overwritten.

Fiscal Year (`FiscalYearService::ensureCurrentFiscalYear()`) and Payment
Methods (`PaymentMethodService::seedDefaults()`) are deliberately **not**
part of this eager wizard — they're lazily seeded the first time the business
touches the relevant screen/flow instead.

## Print Configuration (used beyond just the Settings screen)

`App\Services\Concrete\Admin\PrintSettingResolverService` (singleton) resolves a
business's `PrintSetting` row into an `App\Support\Print\PrintConfig` value object
— `page()`, `isVisible()`, `fieldStyle()`, `orderedHeaderFields()` — consumed by
every report's `pdf()` action and by the shared
`resources/views/admin/partials/print/pdf_header.blade.php` partial. See
[Reports Infrastructure](06-reports-infrastructure.md).

## Website Theme & Public Storefront Settings

`WebsiteThemeSetting` (one row per business) powers both the **Website Theme**
and **Website Settings** tabs. Theme fields (colors, typography, buttons,
presets) are exposed by `GET /api/v1/website-theme/{business_id}`. Public
storefront globals — favicon, SEO, hours, WhatsApp, free delivery, bank
details — are assembled by `SettingService::getWebsitePublicSettings()` /
`resolveWebsitePublicSettings()` and exposed by
`GET /api/v1/website-settings/{business_id}` (business identity from
`businesses`, currency from `accounting_settings`).

**Tab icon (favicon):** admins upload via Settings → Website Settings
(`favicon` stored under `public/uploads/website/`). When `favicon` is null,
`resolveWebsitePublicSettings()` returns the platform Dukanaz asset
`public/assets/img/favicon/favicon-32.png` so the Vue storefront always has
a real URL. The storefront (`frontend_design`) also ships a local copy under
`/favicon/` for the HTML bootstrap before the API responds, and
`applyWebsiteSettings()` falls back to that path if the API value is missing.

## Per-Branch Tax Configuration

Tax rate and behavior (inclusive vs. exclusive) are configured **per branch**,
via Settings → **Tax** tab (`resources/views/admin/setting/tabs/tax.blade.php`),
not business-wide. `branch_tax_settings` (`branch_id` unique/NOT NULL,
`overall_tax_rate`, `card_tax_rate` decimal(5,2), `tax_type` enum
`inclusive`|`exclusive`, default `exclusive`) holds one mandatory row per
branch — `SettingService::getBranchTaxSetting()` auto-creates a
0%/`exclusive` row the first time a branch is read, and
`updateBranchTaxSetting()` saves changes from the Tax tab (same
`firstOrNew()->fill()->save()` + `auditSetting()` shape as every other
`updateXxxSetting()`). `business_settings.overall_tax_rate`/`card_tax_rate`
(the old business-wide columns) are kept only as the migration backfill
source and a defensive fallback — the Business tab no longer exposes them.

`App\Services\Concrete\Admin\TaxSettingResolverService` (registered as a
container singleton, memo + `Cache::remember` like
`ThermalPrintSettingResolverService`) is the **single** resolver every sales
channel calls — `resolve($business_id, $branch_id, $payment_method_ids)`
returns `['rate' => float, 'tax_type' => 'inclusive'|'exclusive',
'tax_discount_rate' => float]`. The Card
Tax Rate applies only when `$payment_method_ids` is non-empty AND every one
of them is a card-type `PaymentMethod`; an empty array (no payment chosen
yet, e.g. a cart preview) always resolves to the Overall rate. Call sites:
- `OrderService::saveLinesAndComputeTotals()` / `recomputeOrderTax()` /
  `revalidateStockOnResume()` — POS, POS Desktop (offline, synced through
  the same `OrderService`), and every order-editing path.
- `WebsiteCartService::buildCartPayload()` — Website and Mobile App (`Mobile\MobileCartService`
  is an empty subclass reusing this same path), using the branch already
  resolved by `resolveFulfillmentContext()`.
- `App\Http\Controllers\Api\Offline\SettingsController::context()` and
  `PosScreenController::index()` ship the resolved rate/type down to the
  offline-desktop client and the browser POS screen's `POS_CONFIG.tax_rates_setting`
  respectively, so their local pre-submit previews mirror the server
  (`public/assets/js/admin/pos-screen.js`'s `effectiveTaxAmount()`/
  `effectiveLineTotal()`) — the server always recomputes authoritatively at
  save/post time regardless of what the client previewed.

**Inclusive vs. exclusive math** (`App\Support\Tax\TaxCalculator`):
- `lineTax($taxable, $percent, $tax_type = 'exclusive', $tax_discount_percent = 0)` — exclusive adds tax
  on top (`taxable * percent / 100`); inclusive backs the tax portion out of
  a price that already contains it (`taxable - taxable / (1 + percent/100)`).
- `lineBreakdown(...)` also returns `tax_discount_amount` when inclusive and
  cash/card rates differ: the price is treated as inclusive of
  `max(overall, card)`, applied tax is the resolved rate, and the leftover
  (`max − applied`) is tax discount. Same rates, or exclusive mode → 0.
  Example: overall 18%, card 8%, card payment → tax 8% + tax discount 10%.
  Overall 8% and card 8% → no tax discount. The customer-facing total never
  changes.
- `lineTotal($taxable, $tax_amount, $tax_type)` — exclusive is additive
  (`taxable + tax_amount`); inclusive is just `taxable` (the price the
  customer sees/types never changes between modes — only how much of it is
  reported as tax vs tax discount does).

The resolved `tax_type` is **stamped onto `orders.tax_type`** at save time
(alongside `orders.tax`/`tax_amount`/`tax_discount`/`tax_discount_amount`)
rather than re-resolved live on every
read, so a reprinted or re-audited historical order always reflects the mode
that was actually in effect at sale time, even if the branch's setting is
changed later. `OrderReturnService` carries forward the original order's
`tax_type` and `tax_discount` (never re-resolves) for the same reason. Thermal
(`admin/order/print/thermal.blade.php`) and normal
(`admin/order/print/print.blade.php`) receipts print `Tax (X%) (Inclusive)` /
`Tax (X%) (Exclusive)` on the tax line, and `Tax Discount (Y%)` when the stamped
amount is greater than zero, read from those stamped columns. POS web, POS
desktop, website cart/checkout/order details, and the mobile app use the same
label shape.

**Accounting impact**: `OrderService::applyPostedEffects()` credits revenue
and tax as two separate JV legs. For an exclusive order, `orders.subtotal`
is already pre-tax, so it's credited to `default_sale_account_id` as-is.
For an inclusive order, `subtotal` is tax-included — crediting it in full
**and** separately crediting `tax_amount` to `default_tax_account_id` would
double-count the tax portion, so the revenue leg backs it out first
(`revenue_amount = subtotal - tax_amount - tax_discount_amount` when
`tax_type === 'inclusive'`). Any tax-discount leftover is credited to
`default_tax_discount_account_id` (Settings → Accounting). Both modes
reconcile to `revenue + tax + tax_discount − discounts == orders.total`.

## Adding a New Settings Domain

1. Migration + model for the new `xxx_settings` table (one row per business,
   `business_id` FK, custom audit columns — see
   [Architecture & Overview](00-architecture.md)).
2. Add a `hasOne` relation on `Business`.
3. Add an `updateXxxSetting()` method to `SettingService` and a section in
   `SettingController`.
4. Add a `POST setting/xxx` route in the `setting` route group.
5. Add the section's form to the Settings Blade view.
6. If it introduces a new permission (uncommon — most settings share
   `setting.manage`), add it to `PermissionRegistry` and re-seed.
