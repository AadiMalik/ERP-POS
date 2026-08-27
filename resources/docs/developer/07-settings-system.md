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
