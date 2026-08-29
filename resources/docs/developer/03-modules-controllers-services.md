# Modules, Controllers & Services

Every admin route lives under one route group in `routes/web.php`:
```php
Route::group(['middleware' => ['auth', 'check.subscription', 'setting', 'must-change-password'], 'prefix' => 'admin'], function () { ... });
```
Sub-groups layer `module:<key>` (subscription-tier gate), `permission:<key>`
(RBAC gate), or `superadmin` on top. This page is the map from **business domain →
controllers → route prefixes**; pair it with
[Database Schema & Relationships](02-database-schema.md) (the tables) and
[Routes & APIs](04-routes-apis.md) (the gating middleware table).

## Roles & Permissions (Core, always on)
`PermissionController`, `RoleController` — `admin/permissions`, `admin/roles`.

## Packages & Business/Tenant Management (Core, platform admin)
`PackageController`, `BusinessController`, `BranchController` — `admin/packages`,
`admin/business`, `admin/branch`.

## Subscriptions & Billing (SaaS)
`SubscriptionController`, `SubscriptionRenewalRequestController`,
`SubscriptionInvoiceController`, `SubscriptionPaymentController`,
`SubscriptionSettingController` (Super Admin, `superadmin` middleware) +
`MySubscriptionController` (business self-service, ungated) — prefixes
`admin/subscriptions`, `admin/subscription-renewal-requests`,
`admin/subscription-invoices`, `admin/subscription-payments`,
`admin/subscription-settings`, `admin/my-subscription`.

Business Admin **My Subscription** shows a Monthly/Yearly price table of active
packages (`FeatureLimitService::compareToPackage` gates downgrade/upgrade
requests). Catalog plans are seeded by `IntroPackageCatalogSeeder` (Starter /
Growth / Business / Enterprise × monthly + yearly, with `discount` % on the
package). Super Admin creates and edits packages via `PackageController`
(price + discount + duration_type + module matrix).

## Users, Customers & Profile (Core CRM/Identity)
`UserController`, `CustomerController`, `ProfileController`, `SearchController`
(global header search — each result group gated by its own module's view
permission) — `admin/users`, `admin/customer`, `admin/profile`, `admin/search`.

Web login / forgot-password lives outside the admin group:
`App\Http\Controllers\Auth\LoginController`,
`ForgotPasswordController` (OTP via `OtpService`, not Laravel's password-reset
broker). Guest screens use `resources/views/layouts/auth.blade.php`. Password
inputs share `resources/views/partials/password-input.blade.php`.
Forgot-password first looks up an active, non-deleted `users` row for the email;
if none exists it stays on the form with “This email is not registered.” and
does not send mail. A registered email gets a Dukanaz-branded OTP
(`emails.otp` / `emails.otp-text`). All website API OTPs
(`send-otp` onboarding/login and `forgot-password`) use
`OtpService::send(..., 'storefront')` — that business’s logo/name/colors with
a Powered by Dukanaz footer (`emails.otp-storefront`). Forgot-password also
checks `CustomerAccountService::emailExistsForBusiness()` for the given
`business_id` before sending.

## Inventory (`module:inventory`)
`WarehouseController`, `BrandController`, `CategoryController`,
`SubCategoryController`, `UnitController`, `ProductController`,
`BarcodeController`, `ProductVariationUnitConversionController`,
`ProductVariationBatchController`, `ProductVariationStockController`,
`ProductVariationStockTransactionController`.

Storefront homepage product rails are built by
`ProductService::buildWebsiteSections()` (attached to
`GET /api/v1/products/{business_id}` on unfiltered page 1, and reused by
`WebsiteHomeService`). Each rail is capped at 12:

| Section | Primary source | Fallback fillers |
|---|---|---|
| Featured | `is_featured = 1` | Other website-visible products (newest first), excluding IDs already in the rail |
| Trending | `is_trending = 1` | Same filler rule |
| New Arrivals | Newest website-visible products | Same filler rule (usually a no-op) |
| Best Sellers | `is_best_seller = 1` | Same filler rule |
| Discounted | Variations with a valid current discount (`discount_percentage > 0` and `discount_apply_all` or matching sale type); resolved discount must stay &gt; 0 | **None** — empty array; storefront hides the whole Discounted section |

Fillers never duplicate a product inside the same section. Themes hide empty
product sections (`v-if` on length) so the homepage never shows a blank rail
or a “Products Not Found” empty state for these groups.

## Purchasing / Procurement (`module:inventory`)
`SupplierController`, `PurchaseRequestController`,
`PurchaseRequestQuotationController`, `PurchaseController` (service:
`PurchaseService`), `GoodReceiptNoteController` (service: `GrnService`),
`PurchaseReturnController` (service: `PurchaseReturnService`),
`OpeningStockController`, `StockTakingController`, `TransferNoteController`,
`SupplierPaymentController`.

## Service Management (`module:service-management`)
`ServicePurchaseController`, `ServicePurchaseReturnController`,
`ServiceSaleController`, `ServiceSaleReturnController`.

## Sales & POS (`module:pos`, + `permission:pos.access` / `permission:order.*`)
Setup: `OrderTypeController`, `PaymentMethodController`, `OrderSourceController`,
`DiscountController`, `SaleTypeController`. Operations: `PosRegisterController`,
`PosRegisterSessionController`, `PosScreenController`, `OrderController` (service:
`OrderService`), `OrderReturnController` (service: `OrderReturnService`),
`CustomerPaymentController` (service: `CustomerPaymentService` — order-targeted
payments may not exceed remaining due; due/amount are compared at the
business `decimal_points` scale so amounts that display as equal, e.g.
Rs 10.61 vs Rs 10.61, are accepted). Customer receivable COA:
`CustomerService::upsertProfile()` (admin Users create/edit and API
`CustomerAccountService::ensureProfile()` / website signup) attaches
`accounting_settings.default_customer_account_id` to `customer_profiles.account_id`
on **create and update**, mirroring `SupplierService::save()` +
`default_supplier_account_id`. Changing either default in
`SettingService::updateAccountingSetting()` runs `syncDefaultAccount()` on
existing customers/suppliers. Customer-payment posting resolves the receivable COA
via `CustomerService::resolveCustomerReceivableAccountId()` (profile COA first,
then `default_customer_account_id`, validated as an active leaf account for the
business); `CustomerPaymentService::applyPosting()` re-resolves at post time inside
the same DB transaction — if neither source is valid, posting aborts with
`CustomerService::RECEIVABLE_COA_MISSING_MESSAGE` and no journal entry is created.

**Stock availability/validation in POS:** `OrderService::searchProducts()`,
`getProductsByCategory()` and `resolvePrices()` attach `is_track_stock`/
`available_stock` (current `ProductVariationStock.quantity` at the register's
warehouse) onto every variation they return, so `pos-screen.js` can show it
and block a cart quantity beyond it client-side. This is enforced
server-side in three places, all gated by `allowsNegativeStock()`
(`InventorySetting.negative_stock` - see
[Settings System](07-settings-system.md)): `saveLinesAndComputeTotals()`
(hold/draft save), `post()` (checkout - locked, authoritative, decrements
stock), and `revalidateStockOnResume()` (runs inside `resume()`, before a
held order's status flips back to draft - removes a line that's gone out of
stock and clamps one whose held quantity now exceeds what's available,
returning the adjustments as `stock_warnings` on the resumed order for the
cashier).

**POS screen layout (`resources/views/admin/pos/screen/index.blade.php`,
`public/assets/js/admin/pos-screen.js`, `public/assets/css/admin/pos-screen.css`):**
customer picker is a native compact `<select>` in the cart header (same styling as
Sale Type); payment method (default **Cash** via `selectDefaultPaymentMethod()`),
order discount, voucher, and delivery address sit in a collapsible panel toggled
by a side bookmark clip (`#posCheckoutToggle` on `#posCheckoutWrap`, collapsed by default — toggling adds/removes `.checkout-open`
on `#posMainCol` so the product grid flexes). Delivery order types show address +
payment method on one row inside that panel and auto-expand it
(`updateDeliveryAddressVisibility()`).

**Order status changes:** there is no generic status dropdown. Status moves via
dedicated actions: POS (`hold`, `resume`, `complete` → `post()`), Admin Order
Detail (`OrderController::changeStatus()` → `OrderService::changeStatus()` —
`POST admin/order/change-status`), and list-row Cancel (`order.cancel`). Posted/
cancelled/void delegate to `post()`/`cancel()`/`void()`; delivery fulfilment
steps (`shipped`, `out_for_delivery`, `delivered`) are lightweight
`transitionStatus()` updates on `DELIVERY` order type only (no second stock/GL
hit). Website hold orders have no `cashier_id`; POS Held Orders passes
`include_null_cashier=1` so they appear alongside the session cashier's own
holds. `CustomerOrderService::mapStatus()` maps ERP `posted` → storefront
`processing` so fulfilment steps can advance before `delivered`.

## Accounting (`module:accounting`)
Core: `AccountTypeController`, `AccountSubTypeController`,
`ExpenseCategoryController`, `AccountController`, `JournalController`,
`JournalEntryController`, `RecurringTransactionController`, `VoucherController`
(service: `VoucherService` — the full promotional rule engine: percent/fixed/
BOGO/buy-X-get-Y, scheduling, min-order/max-discount, usage limits, and
product/category/brand/variation/customer/branch/sale-type/order-type/order-
source/payment-method targeting; `isApplicable()` is the single eligibility
gate and `calculate()`/`eligibleForCart()` are called from
`OrderService::saveLinesAndComputeTotals()` and the POS "browse"/"apply"
endpoints — see `OrderController::eligibleVouchers()`/`previewVoucher()`).
`DiscountController` (Sales & POS, service: `DiscountService`) stays a simple
named flat-rate discount with no conditions - deliberately not merged with
Voucher's rule engine (see `resources/docs/developer/02-database-schema.md`).
Advanced (`App\Http\Controllers\Admin\Accounting`): `FiscalYearController`,
`AccountingPeriodController` (service: `AccountingPeriodService`),
`PeriodClosingRuleController`, `BudgetController`. Expenses: `ExpenseController`,
`AdminExpenseController`. Also two ungated cross-cutting popup controllers reused
from Orders/Purchases/Expenses: `JournalVoucherViewController`,
`StockConsumptionViewController`.

## HRM (`module:hrm`) — `App\Http\Controllers\Admin\Hrm`
`DepartmentController`, `DesignationController`, `ShiftController`,
`EmployeeController`, `AttendanceController`, `LeaveTypeController`,
`LeaveRequestController`, `SalaryComponentController`,
`EmployeeSalaryStructureController`, `EmployeeAdvanceController`,
`EmployeeDeductionController`, `EmployeeLedgerController`,
`EmployeeExitController`, `AssetController`, `AssetAllocationController`, plus
Employee Self-Service under `Hrm\Ess`: `EssDashboardController`,
`EssAttendanceController`, `EssLeaveController`, `EssPayslipController`,
`EssProfileController`, `EssAdvanceController`, `EssExitController`.

## Payroll (`module:payroll` — independent toggle from `hrm`)
`Hrm\PayrollController` (service: `Hrm\PayrollService`), `Hrm\PayslipController`.

## Reports (cross-domain, `App\Http\Controllers\Admin\Reports`)
Uniform `index / data / print / pdf / export / export-csv` shape registered via a
loop over a controller map in `routes/web.php`. ~90 report classes across HRM
(`Reports\Hrm\{Employee,Attendance,Leave,Lifecycle}`, `module:hrm`), Payroll
(`Reports\Hrm\PayrollFinance`, `module:payroll`), Procurement (`module:inventory`),
Customer/POS (`module:pos`), Service Management (`module:service-management`), and
Accounting/Financial (`module:accounting`). See
[Reports Infrastructure](06-reports-infrastructure.md).

## Settings (Core)
`SettingController` (service: `SettingService`) — `admin/setting`, one controller
with a per-domain update action (business, accounting, inventory, customer,
supplier, email, SMS, WhatsApp, FBR, POS, PRA, print, thermal-print, barcode,
theme, notification).

## Documentation (Core — this system)
`DocumentationController` (service: `DocumentationService`) — `admin/documentation`.
See [The Documentation System Itself](12-documentation-system.md).

## Audit, Security & Notifications (Core)
`ActivityLogController`, `LoginHistoryController`, `NotificationController` —
`admin/activity-log`, `admin/login-history`, `admin/notifications`.

## Push Notifications (FCM)
`NotificationTemplateController`, `BroadcastNotificationController` —
`admin/notification-template`, `admin/broadcast-notification`.
Firebase credentials: Settings → Firebase tab (`SettingController::updateFirebaseSetting`,
`SettingService::getFirebaseSetting` / `updateFirebaseSetting`).
Reusable sender: `App\Services\Concrete\Firebase\FirebaseNotificationService`.
Queued worker: `ProcessBroadcastNotificationJob`. See
[FCM Broadcast Notifications](13-fcm-broadcast-notifications.md).

## Shared Concerns
`App\Http\Controllers\Admin\Concerns\HasLookupTypeCrudActions` — shared trait for
simple lookup-type CRUD controllers (e.g. Order Type/Source, Payment Method) to
avoid duplicating index/store/status boilerplate.
