<?php

namespace App\Support\Subscription;

use App\Models\Business;
use App\Services\Concrete\Admin\FeatureLimitService;

/**
 * Single source of truth for every subscription-gated module/CRUD. Consumed
 * by:
 *  - the Package Create/Edit "Module & Limits" matrix (Super Admin)
 *  - FeatureLimitService (hasModule()/check()/usage())
 *  - PermissionRegistry-based Role Create/Edit filtering (package-aware)
 *  - the Business Admin "My Subscription" usage/comparison views
 *
 * Module keys reuse App\Support\Permissions\PermissionRegistry's module keys
 * 1:1 wherever a direct correspondence exists, so package-aware permission
 * filtering is a simple key intersection. The five legacy umbrella flags
 * (hrm, payroll, inventory, accounting, pos) are also registry entries in
 * their own right - they are exactly what the existing `module:<key>` route
 * group middleware in routes/web.php already gates - every other gated
 * module declares that umbrella as its `parent`, so access requires both the
 * umbrella and the module's own toggle to be enabled.
 *
 * Types:
 *  - core:    always enabled, no limit, not shown in the Package UI.
 *  - feature: on/off toggle, no numeric limit.
 *  - limited: on/off toggle + numeric limit when enabled.
 *
 * Whenever a new module/CRUD is added to the system, add its entry here
 * first (see CLAUDE.md's Permissions & Access Control section for the
 * equivalent PermissionRegistry rule - this registry follows the same
 * "register before use" discipline).
 */
class SubscriptionModuleRegistry
{
    public static function modules(): array
    {
        return [
            // ---- Core / platform (always enabled, never shown in Package UI) ----
            'dashboard'       => ['label' => 'Dashboard', 'category' => 'Core', 'type' => 'core'],
            'permission'      => ['label' => 'Permissions', 'category' => 'Core', 'type' => 'core'],
            'role'            => ['label' => 'Roles', 'category' => 'Core', 'type' => 'core'],
            'package'         => ['label' => 'Packages', 'category' => 'Core', 'type' => 'core'],
            'business'        => ['label' => 'Business (Tenants)', 'category' => 'Core', 'type' => 'core'],
            'subscription'    => ['label' => 'Subscriptions & Billing', 'category' => 'Core', 'type' => 'core'],
            'my-subscription' => ['label' => 'My Subscription', 'category' => 'Core', 'type' => 'core'],
            'setting'         => ['label' => 'Settings', 'category' => 'Core', 'type' => 'core'],
            'activity-log'    => ['label' => 'Activity Log', 'category' => 'Core', 'type' => 'core'],
            'login-history'   => ['label' => 'Login History', 'category' => 'Core', 'type' => 'core'],
            'notification'    => ['label' => 'Notifications', 'category' => 'Core', 'type' => 'core'],
            'reports'         => ['label' => 'Reports (Procurement & Accounting)', 'category' => 'Core', 'type' => 'core'],

            // Core, but package-limited (always available, capped)
            'branch' => ['label' => 'Branches', 'category' => 'Core', 'type' => 'limited', 'parent' => null, 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'user'   => ['label' => 'Admin Users', 'category' => 'Core', 'type' => 'limited', 'parent' => null, 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],

            // ---- Inventory (umbrella: is_inventory_enabled) ----
            'inventory'        => ['label' => 'Inventory Module', 'category' => 'Inventory', 'type' => 'feature', 'parent' => null, 'default_enabled' => false],
            'warehouse'        => ['label' => 'Warehouses', 'category' => 'Inventory', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'brand'            => ['label' => 'Brands', 'category' => 'Inventory', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'category'         => ['label' => 'Categories', 'category' => 'Inventory', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'sub-category'     => ['label' => 'Sub Categories', 'category' => 'Inventory', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            // Units are a global/shared master table (no business_id - see
            // UnitService, restricted to Super Admin management) - a
            // per-business record limit does not apply, so this is a
            // feature toggle only, not a `limited` module.
            'unit'             => ['label' => 'Units', 'category' => 'Inventory', 'type' => 'feature', 'parent' => 'inventory', 'default_enabled' => true],
            'product'          => ['label' => 'Products', 'category' => 'Inventory', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'product-variation' => ['label' => 'Product Variations', 'category' => 'Inventory', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'barcode'          => ['label' => 'Barcode / QR Code', 'category' => 'Inventory', 'type' => 'feature', 'parent' => 'inventory', 'default_enabled' => true],
            'unit-conversion'  => ['label' => 'Unit Conversion', 'category' => 'Inventory', 'type' => 'feature', 'parent' => 'inventory', 'default_enabled' => true],
            'batch'            => ['label' => 'Batches', 'category' => 'Inventory', 'type' => 'feature', 'parent' => 'inventory', 'default_enabled' => true],
            'stock'            => ['label' => 'Stock', 'category' => 'Inventory', 'type' => 'feature', 'parent' => 'inventory', 'default_enabled' => true],
            'stock-transaction' => ['label' => 'Stock Transactions', 'category' => 'Inventory', 'type' => 'feature', 'parent' => 'inventory', 'default_enabled' => true],
            'opening-stock'    => ['label' => 'Opening Stock', 'category' => 'Inventory', 'type' => 'feature', 'parent' => 'inventory', 'default_enabled' => true],
            'stock-taking'     => ['label' => 'Stock Taking', 'category' => 'Inventory', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'transfer-note'    => ['label' => 'Stock Transfer / Transfer Notes', 'category' => 'Inventory', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],

            // ---- Purchases (routed under module:inventory today) ----
            'supplier'                   => ['label' => 'Suppliers', 'category' => 'Purchases', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'purchase-request'           => ['label' => 'Purchase Requests', 'category' => 'Purchases', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'purchase-request-quotation' => ['label' => 'Quotations', 'category' => 'Purchases', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'purchase'                   => ['label' => 'Purchases', 'category' => 'Purchases', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'good-receipt-note'          => ['label' => 'Goods Receipt Notes (GRNs)', 'category' => 'Purchases', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'purchase-return'            => ['label' => 'Purchase Returns', 'category' => 'Purchases', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'supplier-payment'           => ['label' => 'Vendor / Supplier Payments', 'category' => 'Purchases', 'type' => 'limited', 'parent' => 'inventory', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],

            // ---- Service Management (non-stock purchase/sale: gas cylinders,
            // decoration, rental/installation/delivery charges, etc). Its own
            // umbrella, independent of inventory/pos, since these transactions
            // never touch stock. ----
            'service-management'      => ['label' => 'Service Management Module', 'category' => 'Service Management', 'type' => 'feature', 'parent' => null, 'default_enabled' => false],
            'service-purchase'        => ['label' => 'Service Purchases', 'category' => 'Service Management', 'type' => 'limited', 'parent' => 'service-management', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'service-purchase-return' => ['label' => 'Service Purchase Returns', 'category' => 'Service Management', 'type' => 'limited', 'parent' => 'service-management', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'service-sale'            => ['label' => 'Service Sales', 'category' => 'Service Management', 'type' => 'limited', 'parent' => 'service-management', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'service-sale-return'     => ['label' => 'Service Sale Returns', 'category' => 'Service Management', 'type' => 'limited', 'parent' => 'service-management', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],

            // ---- Accounting (umbrella: is_accounting_enabled) ----
            'accounting'         => ['label' => 'Accounting Module', 'category' => 'Accounting', 'type' => 'feature', 'parent' => null, 'default_enabled' => false],
            'account-type'       => ['label' => 'Account Types', 'category' => 'Accounting', 'type' => 'feature', 'parent' => 'accounting', 'default_enabled' => true],
            'account-sub-type'   => ['label' => 'Account Sub Types', 'category' => 'Accounting', 'type' => 'feature', 'parent' => 'accounting', 'default_enabled' => true],
            'account'            => ['label' => 'Chart of Accounts', 'category' => 'Accounting', 'type' => 'limited', 'parent' => 'accounting', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'journal'            => ['label' => 'Journals', 'category' => 'Accounting', 'type' => 'feature', 'parent' => 'accounting', 'default_enabled' => true],
            'journal-entry'      => ['label' => 'Journal Entries', 'category' => 'Accounting', 'type' => 'limited', 'parent' => 'accounting', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'bank-reconciliation' => ['label' => 'Bank Reconciliation', 'category' => 'Accounting', 'type' => 'feature', 'parent' => 'accounting', 'default_enabled' => true],
            'recurring-transaction' => ['label' => 'Recurring Transactions', 'category' => 'Accounting', 'type' => 'limited', 'parent' => 'accounting', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'voucher'            => ['label' => 'Vouchers', 'category' => 'Accounting', 'type' => 'limited', 'parent' => 'accounting', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'fiscal-year'        => ['label' => 'Fiscal Years', 'category' => 'Accounting', 'type' => 'feature', 'parent' => 'accounting', 'default_enabled' => true],
            'accounting-period'  => ['label' => 'Accounting Periods', 'category' => 'Accounting', 'type' => 'feature', 'parent' => 'accounting', 'default_enabled' => true],
            'budget'             => ['label' => 'Budgeting', 'category' => 'Accounting', 'type' => 'limited', 'parent' => 'accounting', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'fixed-asset-category' => ['label' => 'Fixed Asset Categories', 'category' => 'Accounting', 'type' => 'feature', 'parent' => 'accounting', 'default_enabled' => true],
            'fixed-asset'        => ['label' => 'Fixed Assets', 'category' => 'Accounting', 'type' => 'limited', 'parent' => 'accounting', 'default_enabled' => true, 'default_limit' => 50, 'unlimited_allowed' => true],
            'fixed-asset-depreciation' => ['label' => 'Fixed Asset Depreciation', 'category' => 'Accounting', 'type' => 'feature', 'parent' => 'accounting', 'default_enabled' => true],

            // ---- Expenses ----
            'expense'          => ['label' => 'Expense Details', 'category' => 'Expenses', 'type' => 'limited', 'parent' => 'accounting', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'expense-category' => ['label' => 'Expense Categories', 'category' => 'Expenses', 'type' => 'limited', 'parent' => 'accounting', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'admin-expense'    => ['label' => 'Admin Expenses', 'category' => 'Expenses', 'type' => 'limited', 'parent' => 'accounting', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],

            // ---- Sales & POS (umbrella: is_pos_enabled) ----
            'pos'            => ['label' => 'POS Module', 'category' => 'Sales & POS', 'type' => 'feature', 'parent' => null, 'default_enabled' => false],
            'order-type'     => ['label' => 'Order Types', 'category' => 'Sales & POS', 'type' => 'feature', 'parent' => 'pos', 'default_enabled' => true],
            'payment-method' => ['label' => 'Payment Types', 'category' => 'Sales & POS', 'type' => 'limited', 'parent' => 'pos', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'order-source'   => ['label' => 'Order Sources', 'category' => 'Sales & POS', 'type' => 'feature', 'parent' => 'pos', 'default_enabled' => true],
            'discount'       => ['label' => 'Discounts', 'category' => 'Sales & POS', 'type' => 'limited', 'parent' => 'pos', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'customer'       => ['label' => 'Customers', 'category' => 'Sales & POS', 'type' => 'limited', 'parent' => 'pos', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'offline-pos'    => ['label' => 'Offline Desktop POS', 'category' => 'Sales & POS', 'type' => 'feature', 'parent' => 'pos', 'default_enabled' => true],

            // ---- Orders ----
            'order' => ['label' => 'Orders', 'category' => 'Orders', 'type' => 'limited', 'parent' => 'pos', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'order-reports' => ['label' => 'Order Reports', 'category' => 'Orders', 'type' => 'feature', 'parent' => 'pos', 'default_enabled' => true],

            // ---- HRM & Payroll (umbrellas: is_hrm_enabled / is_payroll_enabled) ----
            'hrm'                 => ['label' => 'HRM Module', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => null, 'default_enabled' => false],
            'department'          => ['label' => 'Departments', 'category' => 'HRM & Payroll', 'type' => 'limited', 'parent' => 'hrm', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'designation'         => ['label' => 'Designations', 'category' => 'HRM & Payroll', 'type' => 'limited', 'parent' => 'hrm', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'shift'               => ['label' => 'Shifts', 'category' => 'HRM & Payroll', 'type' => 'limited', 'parent' => 'hrm', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'employee'            => ['label' => 'Employees', 'category' => 'HRM & Payroll', 'type' => 'limited', 'parent' => 'hrm', 'default_enabled' => true, 'default_limit' => 5, 'unlimited_allowed' => true],
            'attendance'          => ['label' => 'Attendance', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],
            'leave-type'          => ['label' => 'Leave Types', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],
            'leave-request'       => ['label' => 'Leave Requests', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],
            'salary-component'    => ['label' => 'Salary Components', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],
            'salary-structure'    => ['label' => 'Salary Structures', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],
            'employee-advance'    => ['label' => 'Employee Advances', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],
            'employee-deduction'  => ['label' => 'Employee Deductions', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],
            'employee-ledger'     => ['label' => 'Employee Ledger', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],
            'employee-exit'       => ['label' => 'Resignation / Termination', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],
            'employee-clearance'  => ['label' => 'Employee Clearance', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],
            'asset'               => ['label' => 'Assets', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],
            'asset-allocation'    => ['label' => 'Asset Allocation', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],
            'ess'                 => ['label' => 'Employee Self Service', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],
            'hrm-reports'         => ['label' => 'HRM Reports', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'hrm', 'default_enabled' => true],

            'payroll'         => ['label' => 'Payroll', 'category' => 'HRM & Payroll', 'type' => 'limited', 'parent' => null, 'default_enabled' => false, 'default_limit' => 5, 'unlimited_allowed' => true],
            'payslip'         => ['label' => 'Salary Slips', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'payroll', 'default_enabled' => true],
            'payroll-reports' => ['label' => 'Payroll Reports', 'category' => 'HRM & Payroll', 'type' => 'feature', 'parent' => 'payroll', 'default_enabled' => true],

            // ---- Manufacturing (umbrella - package-tier gate only, same as
            // hrm/payroll; no separate business-level on/off toggle) ----
            'manufacturing'       => ['label' => 'Manufacturing Module', 'category' => 'Manufacturing', 'type' => 'feature', 'parent' => null, 'default_enabled' => false],
            'recipe'              => ['label' => 'Recipes / BOM', 'category' => 'Manufacturing', 'type' => 'limited', 'parent' => 'manufacturing', 'default_enabled' => true, 'default_limit' => 20, 'unlimited_allowed' => true],
            'manufacturing-plan'  => ['label' => 'Manufacturing Plans', 'category' => 'Manufacturing', 'type' => 'limited', 'parent' => 'manufacturing', 'default_enabled' => true, 'default_limit' => 20, 'unlimited_allowed' => true],
            'production'          => ['label' => 'Productions', 'category' => 'Manufacturing', 'type' => 'limited', 'parent' => 'manufacturing', 'default_enabled' => true, 'default_limit' => 50, 'unlimited_allowed' => true],
            'manufacturing-reports' => ['label' => 'Manufacturing Reports', 'category' => 'Manufacturing', 'type' => 'feature', 'parent' => 'manufacturing', 'default_enabled' => true],
        ];
    }

    public static function find(string $moduleKey): ?array
    {
        return self::modules()[$moduleKey] ?? null;
    }

    /**
     * Module keys grouped by category, in registry order - for the Package
     * Create/Edit Module & Limits matrix. Only non-core modules are included.
     */
    public static function grouped(): array
    {
        $out = [];

        foreach (self::modules() as $key => $module) {
            if ($module['type'] === 'core') {
                continue;
            }

            $out[$module['category']][$key] = $module;
        }

        return $out;
    }

    /**
     * Every module key whose type is 'limited' or 'feature' (i.e. every key
     * that should get a package_modules row).
     */
    public static function gatedKeys(): array
    {
        return array_keys(array_filter(self::modules(), fn ($m) => $m['type'] !== 'core'));
    }

    public static function isLimited(string $moduleKey): bool
    {
        $module = self::find($moduleKey);

        return $module && $module['type'] === 'limited';
    }

    /**
     * Every registry key that should be selectable in the Role Create/Edit
     * permission matrix for the given business - every `core` key (always
     * available) plus every other key whose module (and parent umbrella, if
     * any) is enabled on the business's current package. Used to
     * package-aware-filter PermissionRegistry::grouped().
     */
    public static function enabledPermissionModuleKeysFor(Business $business): array
    {
        $service = app(FeatureLimitService::class);
        $keys = [];

        foreach (self::modules() as $key => $meta) {
            if ($meta['type'] === 'core' || $service->hasModule($key, $business)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}
