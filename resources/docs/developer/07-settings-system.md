# Settings System

## Shape

There is **no single generic `Setting` model/config-in-db table**. Instead, each
settings domain is its own Eloquent model — `BusinessSetting`, `AccountingSetting`,
`CustomerSetting`, `SupplierSetting`, `InventorySetting`, `EmailSetting`,
`SmsSetting`, `WhatsappSetting`, `FirebaseSetting`, `LoginSecuritySetting`, `FbrSetting`, `PraSetting`, `PrintSetting`,
`BarcodeSetting`, `ThemeSetting`, `ThermalPrintSetting`, `NotificationSetting`,
`BusinessIntelligenceSetting`,
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

## Social Login & Security

One tab (`resources/views/admin/setting/tabs/login-security.blade.php`,
nav target `#login_security`) bundles three independently-toggled provider
configs into a single `LoginSecuritySetting` row per business — Google Login,
Facebook Login, and CAPTCHA (Google reCAPTCHA v2) — rather than three
separate tabs/tables/permissions, since all three are small, always edited
together, and don't need independent lifecycle. Each section has its own
`is_google_enabled`/`is_facebook_enabled`/`is_captcha_enabled` boolean
(`required_if:is_*_enabled,1` on its credential fields, enforced in
`SettingController::updateLoginSecuritySetting()`) and its own
show/hide-on-toggle JS (`.google-config-field`/`.facebook-config-field`/
`.captcha-config-field`, same idiom as the FBR/PRA/WhatsApp tabs).

`facebook_app_secret` and `recaptcha_secret_key` use Laravel's `encrypted`
cast and are `$hidden` on the model (mirrors `FirebaseSetting::private_key`)
— blank on save means "keep the existing value"
(`SettingController::updateLoginSecuritySetting()` rejects enabling
Facebook/CAPTCHA with neither a new secret submitted nor one already stored).
`google_client_id`/`google_android_client_id`/`google_ios_client_id`,
`facebook_app_id`, and `recaptcha_site_key` are not secret — they're also
returned by the public `website-settings` endpoint (see
[Modules, Controllers & Services](03-modules-controllers-services.md) for how
the storefront/mobile auth endpoints and public config payload consume this).

Permission: `login-security-setting.manage` (OR'd with the blanket
`setting.manage` on the controller, same pattern as `firebase-setting.manage`
— see [Permissions & Access Control](05-permissions-access-control.md)).

There is deliberately **no platform-wide `.env`/`config('services.*')`
fallback** for any of the three — every business must configure its own
Google Cloud / Facebook Developer / reCAPTCHA project. A business that
hasn't enabled a provider gets a clean "not enabled for this business" error
from the login endpoint (CAPTCHA instead just isn't required for that
business, since it's protection, not a login method).

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
- `AccountingSetting.default_inventory_cost_adjustment_account_id` (Settings →
  Accounting) is the P&L counterpart of a Cost Price Adjustment JV — Dr/Cr
  Inventory vs this account, never mixed with `default_stock_adjustment_account_id`
  (physical loss / count variance). Template-seeded to "Inventory Cost
  Adjustment / Revaluation" (`520004-002`, under Stock Adjustment) and cloned
  to every new business by the wizard below. See
  [Cost Price Adjustment](25-cost-price-adjustment.md).
- `AccountingSetting.default_delivery_charge_account_id` (Settings →
  Accounting) is credited by `OrderService::applyPostedEffects()` for a
  posted order's `delivery_charge`, guarded the same way as Round Off (throws
  if unconfigured and the amount is non-zero). Template-seeded to "Delivery
  Charges Income" (490001-004, under Other Income) and cloned to every new
  business by the wizard below, same as every other `default_*_account_id`
  field. See Delivery Zones in
  [Modules, Controllers & Services](03-modules-controllers-services.md).
- `AccountingSetting.default_complimentary_expense_account_id` (Settings →
  Accounting) is debited by `OrderService::applyPostedEffects()` for the actual
  inventory cost of complimentary lines (credited to Inventory; never sales
  revenue). Template-seeded to "Complimentary / Promotional Expense"
  (`540001-002`, under Selling & Distribution) and cloned to every new business.
  Posting a complimentary order/return throws if the mapping is empty.
- `BusinessIntelligenceSetting` (Settings → Business Intelligence) holds the
  classification thresholds for
  [Business Summary](06-reports-infrastructure.md#business-summary--business-health-report)
  (`discount_change_threshold_percent` default 5, dead-stock days 90, and the
  rest of the BI tab). The report also reads `InventorySetting.near_expiry_days`
  / `low_stock_quantity` and `NotificationSetting.credit_limit_threshold_percent`
  rather than duplicating those knobs. New businesses get a row from
  `firstOrCreate`; existing businesses are backfilled by
  `2026_09_12_120002_backfill_business_intelligence_settings_for_existing_businesses`.

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

The Settings tab that drives this is now labelled **Report** (was "Print" —
`settings.tab_print` / `settings.print_title`, renamed in-place across every
locale; the underlying route/controller/permission names are unchanged).

**Header/footer letterhead templates.** Alongside the existing per-field
visibility/order/font/color controls, the Header and Footer sub-tabs
(`resources/views/admin/setting/tabs/print/header.blade.php` and
`footer.blade.php`) now open with an 8-design template carousel (8 header
designs: `classic`, `modern`, `minimal`, `boxed`, `elegant`, `corporate`,
`bold`, `compact` — each a genuinely different layout, e.g. `modern`/`corporate`
are solid/two-tone color panels, `elegant` re-flows to a centered stacked
layout, `compact` folds every field onto one line, not just a border/line
variation; 8 footer designs: the same set minus `elegant`, plus `centered` and
`divided`). Each slide is a live, real preview — server-rendered by calling the
actual `admin.partials.print.header` / `.footer` partials with the business's
current field settings and a dummy sample business, wrapped in a full-width
`<iframe srcdoc="…">` (loads the real `public/assets/css/print.css`, at natural
size, no scale-down) so the preview is pixel-identical to production output,
not a mockup — the admin can judge each design without having to open an
actual report. It's a Bootstrap 5 `.carousel` (`data-bs-interval="false"`, no
new dependency), opening on whichever design is currently selected. Picking a
slide just checks its radio input (`header_config[template]` /
`footer_config[template]`, one per slide, submitted with the form regardless
of which slide is currently showing since Bootstrap only `display:none`s the
inactive ones); the existing field-customization controls stay visible
underneath so the admin can still fine-tune after picking a template.

Several of the templates (`modern`, `corporate`, the `bold` header's title
chip) reverse the text to white on a colored background. Because each header
field already carries its own admin-configured inline `color` style (from the
field table above), that inline style would otherwise always win over the
template's CSS class — those specific color rules in `print.css` are marked
`!important` deliberately, scoped to that one template class, to keep the
reversed text legible. Footer fields have no per-field inline styling, so no
footer rule needs it.

The selected key is stored as `header_config.template` /
`footer_config.template` inside the existing JSON columns (no migration —
`PrintConfig::headerTemplate()` / `footerTemplate()` default to `'classic'`
when absent, so old rows keep rendering exactly as before). It reaches actual
output two ways:
- **Browser print** (`admin.partials.print.header` / `.footer`): the template
  key becomes a `print-template-header-{key}` / `print-template-footer-{key}`
  wrapper class; the visual differences live entirely in
  `public/assets/css/print.css` (appended block after the base `.print-header`
  / `.print-footer` rules). `layouts/print.blade.php` links that stylesheet
  with a `?v={{ filemtime(...) }}` cache-busting query so a browser that
  already cached the old `print.css` (e.g. from before this template feature
  shipped) picks up new/changed template CSS immediately instead of needing a
  hard refresh.
- **PDF (dompdf)** (`pdf_header.blade.php`): dompdf can't load the external
  stylesheet, so the same header designs are reproduced as a small inline-style
  lookup keyed by template name directly in that partial. There's no PDF
  footer partial today (PDF reports have never rendered a footer), so footer
  templates only affect browser/print output.

**Right-side document meta alignment.** The header's right column (Document
No / Date / each module's own `$reference` array — e.g. Purchase's Supplier,
Warehouse, Purchase Request No., Expected Delivery Date) used to render as one
plain `text-align:right` paragraph per row, which staggers the values out of
line the moment two rows have differently-long labels (a long label pushes
its whole row wider, so the row's right-aligned edge no longer matches shorter
rows). Both `header.blade.php` (a CSS `display:table`/`table-row`/`table-cell`
grid, via a `.doc-meta-table` wrapper — the value is now wrapped in
`<span class="doc-meta-value">` specifically so it's a distinct table-cell)
and `pdf_header.blade.php` (a real nested `<table>`, since dompdf needs actual
table markup rather than `display:table`) now render this as a proper
2-column grid, so every row's label and value line up regardless of label
length. `elegant` (centered single-line composition) and `compact` (folds
everything onto one dense line) opt back out of the grid with their own
`.doc-meta-table` overrides — see the comments in `print.css` next to each.

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
5. Add the section's form to the Settings Blade view: a nav button (with a
   representative FontAwesome icon, matching the existing tabs) plus its
   `tab-pane` in `resources/views/admin/setting/index.blade.php`, and the
   form partial itself in `resources/views/admin/setting/tabs/`.
6. If it introduces a new permission (uncommon — most settings share
   `setting.manage`), add it to `PermissionRegistry` and re-seed.
