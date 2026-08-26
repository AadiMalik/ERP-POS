# Database Schema & Relationships

242 migrations, 137 models. See [Architecture & Overview](00-architecture.md) for
the multi-tenancy convention (`business_id`/`branch_id`, manually filtered, no
global scope) and the UUID-PK/custom-audit-column convention that applies to every
table below.

## Tenancy / Platform

`packages` (plan definitions, per-module limits/flags) → `businesses` (root tenant)
→ `business_subscriptions` → `subscription_invoices`, `subscription_payments`,
`subscription_renewal_requests`, `subscription_history`,
`subscription_reminder_logs`, `subscription_settings`. `package_modules` holds the
module/feature gating matrix per package (see
[Subscription & Module Gating](08-subscription-module-gating.md)). `branches`
belong to a business.

**Core models:** `Business` (`belongsTo Package`; `hasMany BusinessSubscription`;
`hasOne` relation to all 16 per-business Setting models — see
[Settings System](07-settings-system.md)); `Branch` (`belongsTo Business`);
`BusinessSubscription` (`belongsTo Business, Package`; `hasMany SubscriptionInvoice,
SubscriptionHistory`).

## Auth / Roles / Permissions

`users` (Laravel default + `business_id`, `branch_id`, nullable password for
customer-only accounts, `must_change_password`) — Spatie `permissions`, `roles`
(customized with a nullable `business_id`, i.e. **roles are tenant-scoped**,
permissions are global), `model_has_permissions`, `model_has_roles`,
`role_has_permissions`. `personal_access_tokens` (Sanctum).

**Core models:** `User` (extends `Authenticatable`; `HasApiTokens, Notifiable,
HasRoles`; `belongsTo Business, Branch`; `hasMany CustomerProfile`; `hasOne
Employee`); `Role` (extends `Spatie\Permission\Models\Role`, adds `belongsTo
Business`).

## Settings (one row per business — a table per domain)

`business_settings`, `inventory_settings`, `accounting_settings`, `pos_settings`,
`customer_settings`, `supplier_settings`, `email_settings`, `whatsapp_settings`,
`sms_settings`, `fbr_settings`, `pra_settings`, `print_settings`, `theme_settings`,
`barcode_settings`, `thermal_print_settings`, `notification_settings`. See
[Settings System](07-settings-system.md).

## Inventory / Catalog

`warehouses`, `categories`, `sub_categories`, `brands`, `units`, `products` (→
`product_images`, `product_features`), `product_variations` (the actual
sellable/stockable unit — SKU, barcode/QR, purchase & sale price — → `
product_variation_attributes`, `product_variation_unit_conversions`,
`product_variation_stocks` (per-warehouse qty), `product_variation_stock_transactions`
(the stock ledger/audit trail), `product_variation_batches` (batch/expiry),
`product_variation_prices`, `product_variation_price_histories`), `opening_stocks`/
`_details`, `stock_takings`/`_details`, `transfer_notes`/`_details`.

**Core models:** `Product` (`belongsTo Business, Category, SubCategory, Brand`;
`hasMany ProductVariation`); `ProductVariation` (`belongsTo Product, Business`;
`belongsTo Unit` three ways — base/purchase/sale; `hasMany
ProductVariationAttribute, ProductVariationPrice, ProductVariationUnitConversion`).

## Purchasing

`suppliers`, `purchases`/`purchase_details` (tracks
`ordered_quantity`/`received_quantity`/`rejected_quantity`/`pending_quantity` for
three-way matching), `good_receipt_notes`/`_details`, `purchase_requests`/`_details`,
`purchase_request_quotations`/`_details`, `purchase_returns`/`_details`,
`supplier_payments`, `service_purchases`/`_details`,
`service_purchase_returns`/`_details` (parallel non-stock family).

**Core models:** `Purchase` (`belongsTo Supplier, PurchaseRequest, Warehouse,
Branch, Business`; `hasMany PurchaseDetail`); `Supplier` (`belongsTo Branch,
Business, Account` — linked to the chart of accounts for AP).

## Sales / POS

`order_types`, `payment_methods`, `order_sources`, `sale_types`, `discounts`,
`vouchers`/`voucher_redemptions`, `pos_registers`, `pos_register_sessions`,
`pos_register_cash_movements`, `order_counters` (daily numbering), `orders`/
`order_details`, `order_payments`, `order_status_history`, `order_returns`/
`_details`, `customer_profiles` (the actual "customer" entity, replacing an earlier
now-removed `customers` table — `belongsTo User, Branch, Business, Account`),
`customer_payments`, `customer_store_credit_transactions`, `otps` (customer OTP
login), `service_sales`/`_details`, `service_sale_returns`/`_details` (parallel
non-stock family). `orders.status` is an ENUM:
`draft`, `hold`, `posted`, `cancelled`, `void`, `returned`, plus delivery
fulfilment values `shipped`, `out_for_delivery`, `delivered` (see migration
`2026_08_26_181500_add_delivery_statuses_to_orders_table`).

**Core models:** `Order` (`belongsTo Business, Branch, Warehouse, PosRegister,
PosRegisterSession, User (cashier), User (customer), OrderType, OrderSource,
SaleType, Discount, Voucher`; `hasMany OrderDetail, OrderPayment, CustomerPayment,
OrderStatusHistory, OrderReturn`); `OrderDetail` (`belongsTo Order, Product,
ProductVariation, Unit, SaleType, Voucher`).

**Removed in migration history — don't chase these:** a standalone `customers`
table (replaced by `users` + `customer_profiles`), a `tax_rates` table (tax is now
inline on orders). `discounts` was deliberately simplified down to a flat named
rate (`name`/`type`/`value`/`status`, no scope, no schedule) — a Discount is a
simple order-level rate the cashier picks manually in POS, nothing else.

**Voucher rule engine:** all conditional/targeting complexity lives on
`vouchers` only (not `discounts`) — `promo_type` (`discount`/`bogo`/
`buy_x_get_y`), `days_of_week`/`time_start`/`time_end` (scheduling),
`max_discount_amount` (percent cap), `is_exclusive` (blocks combining with a
Discount), `buy_quantity`/`get_quantity`/`get_discount_percent` (BOGO/buy-X-
get-Y). Scope pivots (empty = applies to all): `voucher_products`,
`voucher_categories`, `voucher_brands`, `voucher_variations`,
`voucher_customers`, `voucher_order_types`, `voucher_branches`,
`voucher_sale_types`, `voucher_order_sources`, `voucher_payment_methods`, plus
`voucher_get_products`/`voucher_get_categories` for the "get" side of a
buy-X-get-Y when it differs from what was bought. `order_details` and
`order_return_details` carry `voucher_id`/`voucher_discount_amount`/
`free_quantity` per line so eligibility, BOGO free units, and returns can all
be attributed and prorated per item rather than only at the order level. All
eligibility/calculation logic lives in `VoucherService::isApplicable()`/
`calculate()`, called from `OrderService::saveLinesAndComputeTotals()`.

## Accounting

`account_types`, `account_sub_types`, `accounts` (chart of accounts,
self-referencing `parent_account_id`), `journals` (journal *types* — CPV/BPV/PRV/
OSV/ICV/SV/SRV/CRV/BRV/OBV), `journal_entries`/`journal_entry_details`,
`expense_categories`, `expenses`, `recurring_transactions`/`_runs`, `fiscal_years`,
`accounting_periods`, `period_closing_rules`/`_attempts`/`_issues`, `budgets`/
`budget_lines`, `document_send_logs` (generic doc-emailing log).

**Core models:** `Account` (self-referencing tree via `parentAccount`/
`childAccounts`; `belongsTo AccountType, AccountSubType, Business`); `JournalEntry`
(`belongsTo Journal, Business, Branch`; `hasMany JournalEntryDetail`; polymorphic-
style `source_type`/`source_id` linking back to the originating Order, Purchase,
Expense, etc.; supports recurring transactions via `recurring_transaction_id`).

## HRM

`departments`, `designations`, `shifts`, `employees` (tied to `users` via
`user_id`), `employee_documents`, `attendances`, `leave_types`, `leave_requests`,
`salary_components`, `employee_salary_structures`/`_items`, `employee_advances`,
`employee_deductions`, `employee_ledger_entries`, `payroll_runs`, `payslips`/
`_items`, `employee_exits`/`exit_clearances`, `assets`/`asset_allocations`.

**Core models:** `Employee` (`belongsTo User, Department, Designation, Shift,
Business, Branch`; `hasOne EmployeeSalaryStructure` (active); `hasMany
EmployeeDocument, Attendance, LeaveRequest`); `PayrollRun` (`hasMany Payslip`);
`Payslip` (`belongsTo PayrollRun, Employee, Branch`; `hasMany PayslipItem`).

## Reports

No dedicated tables or models — a read-only projection layer over everything
above. See [Reports Infrastructure](06-reports-infrastructure.md).

## Audit / Notifications / Misc

`activity_logs`, `login_histories`, `notifications`/`notification_recipients`,
`import_batches`, `timezones`.
