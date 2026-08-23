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

## Users, Customers & Profile (Core CRM/Identity)
`UserController`, `CustomerController`, `ProfileController`, `SearchController`
(global header search — each result group gated by its own module's view
permission) — `admin/users`, `admin/customer`, `admin/profile`, `admin/search`.

## Inventory (`module:inventory`)
`WarehouseController`, `BrandController`, `CategoryController`,
`SubCategoryController`, `UnitController`, `ProductController`,
`BarcodeController`, `ProductVariationUnitConversionController`,
`ProductVariationBatchController`, `ProductVariationStockController`,
`ProductVariationStockTransactionController`.

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
`CustomerPaymentController`.

## Accounting (`module:accounting`)
Core: `AccountTypeController`, `AccountSubTypeController`,
`ExpenseCategoryController`, `AccountController`, `JournalController`,
`JournalEntryController`, `RecurringTransactionController`, `VoucherController`.
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

## Shared Concerns
`App\Http\Controllers\Admin\Concerns\HasLookupTypeCrudActions` — shared trait for
simple lookup-type CRUD controllers (e.g. Order Type/Source, Payment Method) to
avoid duplicating index/store/status boilerplate.
