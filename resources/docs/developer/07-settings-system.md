# Settings System

## Shape

There is **no single generic `Setting` model/config-in-db table**. Instead, each
settings domain is its own Eloquent model — `BusinessSetting`, `AccountingSetting`,
`CustomerSetting`, `SupplierSetting`, `InventorySetting`, `EmailSetting`,
`SmsSetting`, `WhatsappSetting`, `FirebaseSetting`, `FbrSetting`, `PraSetting`, `PrintSetting`,
`BarcodeSetting`, `ThemeSetting`, `ThermalPrintSetting`, `NotificationSetting`,
`PosSetting` — each presumably one row per business. `Business` exposes a `hasOne`
relation to every one of them.

## Controller/Service

`App\Http\Controllers\Admin\SettingController` (gated by a single
`$this->middleware('permission:setting.manage');` since every section shares one
permission) delegates to `App\Services\Concrete\Admin\SettingService`, which has
one `updateXxxSetting()` method per domain — `routes/web.php`'s `setting` group
maps one `POST` route per section to its update method.

## Settings Consumed Outside the Settings Screen

Most of these rows are read only by `SettingController`/`SettingService`
itself, but some are consumed elsewhere as real business-rule gates:
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
