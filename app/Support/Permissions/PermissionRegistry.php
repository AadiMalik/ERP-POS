<?php

namespace App\Support\Permissions;

/**
 * Single source of truth for every permission in the system, grouped by
 * module. Consumed by:
 *  - database/seeders/PermissionSeeder.php (to create/sync permission rows)
 *  - the Role Create/Edit screen (to render the module-wise permission matrix)
 *
 * Permission names are stored literally (not derived from the module/action
 * key) so pre-existing, already-enforced permission names (e.g.
 * `pos.register.close`, `order.cancel_void`, `expense-category.manage`) can
 * be grouped here for the UI without being renamed.
 *
 * Whenever a new module/CRUD is added, add its permissions here first, then
 * run `php artisan db:seed --class=PermissionSeeder`. See CLAUDE.md.
 */
class PermissionRegistry
{
    public static function modules(): array
    {
        return [
            'dashboard' => ['label' => 'Dashboard', 'actions' => [
                'view' => ['name' => 'dashboard.view', 'label' => 'View', 'is_system' => false],
            ]],

            'permission' => ['label' => 'Permissions', 'actions' => [
                'view'   => ['name' => 'permission.view', 'label' => 'View', 'is_system' => true],
                'create' => ['name' => 'permission.create', 'label' => 'Create', 'is_system' => true],
                'edit'   => ['name' => 'permission.edit', 'label' => 'Edit', 'is_system' => true],
                'delete' => ['name' => 'permission.delete', 'label' => 'Delete', 'is_system' => true],
            ]],

            'role' => ['label' => 'Roles', 'actions' => [
                'view'   => ['name' => 'role.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'role.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'role.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'role.delete', 'label' => 'Delete', 'is_system' => false],
                'reset'  => ['name' => 'role.reset', 'label' => 'Reset Default Roles', 'is_system' => false],
            ]],

            'package' => ['label' => 'Packages', 'actions' => [
                'view'   => ['name' => 'package.view', 'label' => 'View', 'is_system' => true],
                'create' => ['name' => 'package.create', 'label' => 'Create', 'is_system' => true],
                'edit'   => ['name' => 'package.edit', 'label' => 'Edit', 'is_system' => true],
                'delete' => ['name' => 'package.delete', 'label' => 'Delete', 'is_system' => true],
            ]],

            'business' => ['label' => 'Business (Tenants)', 'actions' => [
                'view'   => ['name' => 'business.view', 'label' => 'View', 'is_system' => true],
                'create' => ['name' => 'business.create', 'label' => 'Create', 'is_system' => true],
                'edit'   => ['name' => 'business.edit', 'label' => 'Edit', 'is_system' => true],
                'delete' => ['name' => 'business.delete', 'label' => 'Delete', 'is_system' => true],
            ]],

            'subscription' => ['label' => 'Subscriptions & Billing', 'actions' => [
                // Enforced via constructor middleware on every Subscription*
                // controller, in addition to the `superadmin` route-group middleware.
                'manage' => ['name' => 'subscription.manage', 'label' => 'Manage', 'is_system' => true],
            ]],

            'my-subscription' => ['label' => 'My Subscription', 'actions' => [
                'manage' => ['name' => 'my-subscription.manage', 'label' => 'Manage', 'is_system' => false],
            ]],

            'branch' => ['label' => 'Branch', 'actions' => [
                'view'   => ['name' => 'branch.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'branch.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'branch.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'branch.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'branch.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'user' => ['label' => 'Admin Users', 'actions' => [
                'view'            => ['name' => 'user.view', 'label' => 'View', 'is_system' => false],
                'create'          => ['name' => 'user.create', 'label' => 'Create', 'is_system' => false],
                'edit'            => ['name' => 'user.edit', 'label' => 'Edit', 'is_system' => false],
                'delete'          => ['name' => 'user.delete', 'label' => 'Delete', 'is_system' => false],
                'status'          => ['name' => 'user.status', 'label' => 'Change Status', 'is_system' => false],
                'change_password' => ['name' => 'user.change-password', 'label' => 'Change Password', 'is_system' => false],
                'import' => ['name' => 'user.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'user.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'warehouse' => ['label' => 'Warehouse', 'actions' => [
                'view'   => ['name' => 'warehouse.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'warehouse.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'warehouse.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'warehouse.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'warehouse.status', 'label' => 'Change Status', 'is_system' => false],
                'import' => ['name' => 'warehouse.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'warehouse.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'brand' => ['label' => 'Brands', 'actions' => [
                'view'   => ['name' => 'brand.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'brand.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'brand.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'brand.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'brand.status', 'label' => 'Change Status', 'is_system' => false],
                'import' => ['name' => 'brand.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'brand.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'category' => ['label' => 'Categories', 'actions' => [
                'view'   => ['name' => 'category.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'category.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'category.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'category.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'category.status', 'label' => 'Change Status', 'is_system' => false],
                'import' => ['name' => 'category.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'category.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'sub-category' => ['label' => 'Sub Categories', 'actions' => [
                'view'   => ['name' => 'sub-category.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'sub-category.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'sub-category.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'sub-category.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'sub-category.status', 'label' => 'Change Status', 'is_system' => false],
                'import' => ['name' => 'sub-category.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'sub-category.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'unit' => ['label' => 'Units', 'actions' => [
                'view'   => ['name' => 'unit.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'unit.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'unit.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'unit.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'unit.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'product' => ['label' => 'Products', 'actions' => [
                'view'   => ['name' => 'product.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'product.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'product.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'product.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'product.status', 'label' => 'Change Status', 'is_system' => false],
                'import' => ['name' => 'product.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'product.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'sale-type' => ['label' => 'Sale Types', 'actions' => [
                'view'   => ['name' => 'sale-type.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'sale-type.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'sale-type.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'sale-type.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'sale-type.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'barcode' => ['label' => 'Barcode / QR Code', 'actions' => [
                'manage' => ['name' => 'barcode.manage', 'label' => 'Manage', 'is_system' => false],
            ]],

            'unit-conversion' => ['label' => 'Unit Conversion', 'actions' => [
                'view'   => ['name' => 'unit-conversion.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'unit-conversion.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'unit-conversion.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'unit-conversion.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'unit-conversion.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'batch' => ['label' => 'Batches', 'actions' => [
                'view'   => ['name' => 'batch.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'batch.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'batch.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'batch.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'batch.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'stock' => ['label' => 'Stock', 'actions' => [
                'view'   => ['name' => 'stock.view', 'label' => 'View', 'is_system' => false],
                'status' => ['name' => 'stock.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'stock-transaction' => ['label' => 'Stock Transactions', 'actions' => [
                'view' => ['name' => 'stock-transaction.view', 'label' => 'View', 'is_system' => false],
            ]],

            'account-type' => ['label' => 'Account Types', 'actions' => [
                'view'   => ['name' => 'account-type.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'account-type.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'account-type.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'account-type.delete', 'label' => 'Delete', 'is_system' => false],
                'reset'  => ['name' => 'account-type.reset', 'label' => 'Reset Defaults', 'is_system' => false],
            ]],

            'account-sub-type' => ['label' => 'Account Sub Types', 'actions' => [
                'view'   => ['name' => 'account-sub-type.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'account-sub-type.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'account-sub-type.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'account-sub-type.delete', 'label' => 'Delete', 'is_system' => false],
                'reset'  => ['name' => 'account-sub-type.reset', 'label' => 'Reset Defaults', 'is_system' => false],
            ]],

            'account' => ['label' => 'Accounts', 'actions' => [
                'view'   => ['name' => 'account.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'account.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'account.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'account.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'account.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'journal' => ['label' => 'Journals', 'actions' => [
                'view'   => ['name' => 'journal.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'journal.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'journal.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'journal.delete', 'label' => 'Delete', 'is_system' => false],
            ]],

            'journal-entry' => ['label' => 'Journal Entries', 'actions' => [
                'view'   => ['name' => 'journal-entry.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'journal-entry.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'journal-entry.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'journal-entry.delete', 'label' => 'Delete', 'is_system' => false],
                'print'  => ['name' => 'journal-entry.print', 'label' => 'Print', 'is_system' => false],
                'post'   => ['name' => 'journal-entry.post', 'label' => 'Post / Unpost', 'is_system' => false],
                'import' => ['name' => 'journal-entry.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'journal-entry.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'recurring-transaction' => ['label' => 'Recurring Transactions', 'actions' => [
                'view'         => ['name' => 'recurring-transaction.view', 'label' => 'View', 'is_system' => false],
                'create'       => ['name' => 'recurring-transaction.create', 'label' => 'Create', 'is_system' => false],
                'edit'         => ['name' => 'recurring-transaction.edit', 'label' => 'Edit', 'is_system' => false],
                'delete'       => ['name' => 'recurring-transaction.delete', 'label' => 'Delete', 'is_system' => false],
                'pause'        => ['name' => 'recurring-transaction.pause', 'label' => 'Pause', 'is_system' => false],
                'resume'       => ['name' => 'recurring-transaction.resume', 'label' => 'Resume', 'is_system' => false],
                'cancel'       => ['name' => 'recurring-transaction.cancel', 'label' => 'Cancel', 'is_system' => false],
                'run_now'      => ['name' => 'recurring-transaction.run-now', 'label' => 'Run Now', 'is_system' => false],
                'view_history' => ['name' => 'recurring-transaction.view-history', 'label' => 'View Execution History', 'is_system' => false],
                'import'       => ['name' => 'recurring-transaction.import', 'label' => 'Import', 'is_system' => false],
                'export'       => ['name' => 'recurring-transaction.export', 'label' => 'Export', 'is_system' => false],
            ]],

            // ---- Accounting Automation, Budgeting & Financial Period Management ----
            // Advanced Accounting Mode screens - see CommonFunctions::businessAccountingAdvancedModeEnabled().
            // Simple-mode business owners never see these; the two Simple-mode
            // settings (Accounting Period Closing, Budgeting) ride on the
            // existing setting.manage permission on the Accounting Settings tab.

            'fiscal-year' => ['label' => 'Fiscal Years', 'actions' => [
                'view'   => ['name' => 'fiscal-year.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'fiscal-year.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'fiscal-year.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'fiscal-year.delete', 'label' => 'Delete', 'is_system' => false],
            ]],

            'accounting-period' => ['label' => 'Accounting Periods', 'actions' => [
                'view'   => ['name' => 'accounting-period.view', 'label' => 'View', 'is_system' => false],
                'open'   => ['name' => 'accounting-period.open', 'label' => 'Open', 'is_system' => false],
                'close'  => ['name' => 'accounting-period.close', 'label' => 'Close', 'is_system' => false],
                'reopen' => ['name' => 'accounting-period.reopen', 'label' => 'Reopen', 'is_system' => false],
            ]],

            'period-closing-rule' => ['label' => 'Period Closing Rules', 'actions' => [
                'manage' => ['name' => 'period-closing-rule.manage', 'label' => 'Manage', 'is_system' => false],
            ]],

            'budget' => ['label' => 'Budgets', 'actions' => [
                'view'     => ['name' => 'budget.view', 'label' => 'View', 'is_system' => false],
                'create'   => ['name' => 'budget.create', 'label' => 'Create', 'is_system' => false],
                'edit'     => ['name' => 'budget.edit', 'label' => 'Edit', 'is_system' => false],
                'delete'   => ['name' => 'budget.delete', 'label' => 'Delete', 'is_system' => false],
                'generate' => ['name' => 'budget.generate', 'label' => 'Generate / Regenerate', 'is_system' => false],
            ]],

            'supplier' => ['label' => 'Suppliers', 'actions' => [
                'view'   => ['name' => 'supplier.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'supplier.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'supplier.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'supplier.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'supplier.status', 'label' => 'Change Status', 'is_system' => false],
                'import' => ['name' => 'supplier.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'supplier.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'customer' => ['label' => 'Customers', 'actions' => [
                'view'   => ['name' => 'customer.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'customer.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'customer.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'customer.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'customer.status', 'label' => 'Change Status', 'is_system' => false],
                'import' => ['name' => 'customer.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'customer.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'order-type' => ['label' => 'Order Types', 'actions' => [
                'view'   => ['name' => 'order-type.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'order-type.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'order-type.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'order-type.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'order-type.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'payment-method' => ['label' => 'Payment Methods', 'actions' => [
                'view'   => ['name' => 'payment-method.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'payment-method.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'payment-method.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'payment-method.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'payment-method.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'order-source' => ['label' => 'Order Sources', 'actions' => [
                'view'   => ['name' => 'order-source.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'order-source.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'order-source.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'order-source.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'order-source.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'discount' => ['label' => 'Discounts', 'actions' => [
                'view'   => ['name' => 'discount.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'discount.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'discount.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'discount.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'discount.status', 'label' => 'Change Status', 'is_system' => false],
                'import' => ['name' => 'discount.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'discount.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'voucher' => ['label' => 'Vouchers', 'actions' => [
                'view'   => ['name' => 'voucher.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'voucher.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'voucher.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'voucher.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'voucher.status', 'label' => 'Change Status', 'is_system' => false],
                'import' => ['name' => 'voucher.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'voucher.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'purchase-request' => ['label' => 'Purchase Requests', 'actions' => [
                'view'   => ['name' => 'purchase-request.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'purchase-request.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'purchase-request.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'purchase-request.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'purchase-request.status', 'label' => 'Change Status', 'is_system' => false],
                'print'  => ['name' => 'purchase-request.print', 'label' => 'Print', 'is_system' => false],
                'import' => ['name' => 'purchase-request.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'purchase-request.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'purchase-request-quotation' => ['label' => 'Purchase Request Quotations', 'actions' => [
                'view'   => ['name' => 'purchase-request-quotation.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'purchase-request-quotation.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'purchase-request-quotation.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'purchase-request-quotation.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'purchase-request-quotation.status', 'label' => 'Change Status', 'is_system' => false],
                'print'  => ['name' => 'purchase-request-quotation.print', 'label' => 'Print', 'is_system' => false],
            ]],

            'purchase' => ['label' => 'Purchases', 'actions' => [
                'view'   => ['name' => 'purchase.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'purchase.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'purchase.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'purchase.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'purchase.status', 'label' => 'Change Status', 'is_system' => false],
                'print'  => ['name' => 'purchase.print', 'label' => 'Print', 'is_system' => false],
            ]],

            'good-receipt-note' => ['label' => 'Goods Receipt Notes', 'actions' => [
                'view'   => ['name' => 'good-receipt-note.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'good-receipt-note.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'good-receipt-note.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'good-receipt-note.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'good-receipt-note.status', 'label' => 'Change Status', 'is_system' => false],
                'print'  => ['name' => 'good-receipt-note.print', 'label' => 'Print', 'is_system' => false],
            ]],

            'purchase-return' => ['label' => 'Purchase Returns', 'actions' => [
                'view'    => ['name' => 'purchase-return.view', 'label' => 'View', 'is_system' => false],
                'create'  => ['name' => 'purchase-return.create', 'label' => 'Create', 'is_system' => false],
                'edit'    => ['name' => 'purchase-return.edit', 'label' => 'Edit', 'is_system' => false],
                'delete'  => ['name' => 'purchase-return.delete', 'label' => 'Delete', 'is_system' => false],
                'approve' => ['name' => 'purchase-return.approve', 'label' => 'Approve', 'is_system' => false],
                'print'   => ['name' => 'purchase-return.print', 'label' => 'Print', 'is_system' => false],
            ]],

            'opening-stock' => ['label' => 'Opening Stock', 'actions' => [
                'view'   => ['name' => 'opening-stock.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'opening-stock.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'opening-stock.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'opening-stock.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'opening-stock.status', 'label' => 'Change Status', 'is_system' => false],
                'print'  => ['name' => 'opening-stock.print', 'label' => 'Print', 'is_system' => false],
                'import' => ['name' => 'opening-stock.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'opening-stock.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'stock-taking' => ['label' => 'Stock Taking', 'actions' => [
                'view'   => ['name' => 'stock-taking.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'stock-taking.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'stock-taking.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'stock-taking.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'stock-taking.status', 'label' => 'Change Status', 'is_system' => false],
                'print'  => ['name' => 'stock-taking.print', 'label' => 'Print', 'is_system' => false],
            ]],

            'transfer-note' => ['label' => 'Transfer Note', 'actions' => [
                'view'   => ['name' => 'transfer-note.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'transfer-note.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'transfer-note.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'transfer-note.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'transfer-note.status', 'label' => 'Change Status', 'is_system' => false],
                'print'  => ['name' => 'transfer-note.print', 'label' => 'Print', 'is_system' => false],
                'import' => ['name' => 'transfer-note.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'transfer-note.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'supplier-payment' => ['label' => 'Supplier Payments', 'actions' => [
                'view'   => ['name' => 'supplier-payment.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'supplier-payment.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'supplier-payment.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'supplier-payment.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'supplier-payment.status', 'label' => 'Change Status', 'is_system' => false],
                'print'  => ['name' => 'supplier-payment.print', 'label' => 'Print', 'is_system' => false],
                'import' => ['name' => 'supplier-payment.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'supplier-payment.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'customer-payment' => ['label' => 'Customer Payments', 'actions' => [
                'view'   => ['name' => 'customer-payment.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'customer-payment.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'customer-payment.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'customer-payment.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'customer-payment.status', 'label' => 'Change Status', 'is_system' => false],
                'print'  => ['name' => 'customer-payment.print', 'label' => 'Print', 'is_system' => false],
                'import' => ['name' => 'customer-payment.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'customer-payment.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'setting' => ['label' => 'Settings', 'actions' => [
                'manage' => ['name' => 'setting.manage', 'label' => 'Manage', 'is_system' => false],
            ]],

            // ---- HRM & Payroll modules ----

            'department' => ['label' => 'Departments', 'actions' => [
                'view'   => ['name' => 'department.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'department.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'department.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'department.delete', 'label' => 'Delete', 'is_system' => false],
                'import' => ['name' => 'department.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'department.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'designation' => ['label' => 'Designations', 'actions' => [
                'view'   => ['name' => 'designation.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'designation.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'designation.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'designation.delete', 'label' => 'Delete', 'is_system' => false],
                'import' => ['name' => 'designation.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'designation.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'shift' => ['label' => 'Shifts', 'actions' => [
                'view'   => ['name' => 'shift.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'shift.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'shift.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'shift.delete', 'label' => 'Delete', 'is_system' => false],
                'import' => ['name' => 'shift.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'shift.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'employee' => ['label' => 'Employees', 'actions' => [
                'view'     => ['name' => 'employee.view', 'label' => 'View', 'is_system' => false],
                'create'   => ['name' => 'employee.create', 'label' => 'Create', 'is_system' => false],
                'edit'     => ['name' => 'employee.edit', 'label' => 'Edit', 'is_system' => false],
                'delete'   => ['name' => 'employee.delete', 'label' => 'Delete', 'is_system' => false],
                'status'   => ['name' => 'employee.status', 'label' => 'Change Status', 'is_system' => false],
                'document' => ['name' => 'employee.document', 'label' => 'Manage Documents', 'is_system' => false],
                'import'   => ['name' => 'employee.import', 'label' => 'Import', 'is_system' => false],
                'export'   => ['name' => 'employee.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'attendance' => ['label' => 'Attendance', 'actions' => [
                'view'        => ['name' => 'attendance.view', 'label' => 'View', 'is_system' => false],
                'create'      => ['name' => 'attendance.create', 'label' => 'Create', 'is_system' => false],
                'edit'        => ['name' => 'attendance.edit', 'label' => 'Edit', 'is_system' => false],
                'delete'      => ['name' => 'attendance.delete', 'label' => 'Delete', 'is_system' => false],
                'report_view' => ['name' => 'attendance.report.view', 'label' => 'View Report', 'is_system' => false],
                'import'      => ['name' => 'attendance.import', 'label' => 'Import', 'is_system' => false],
                'export'      => ['name' => 'attendance.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'leave-type' => ['label' => 'Leave Types', 'actions' => [
                'view'   => ['name' => 'leave-type.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'leave-type.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'leave-type.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'leave-type.delete', 'label' => 'Delete', 'is_system' => false],
            ]],

            'leave-request' => ['label' => 'Leave Requests', 'actions' => [
                'view'    => ['name' => 'leave-request.view', 'label' => 'View', 'is_system' => false],
                'create'  => ['name' => 'leave-request.create', 'label' => 'Create', 'is_system' => false],
                'edit'    => ['name' => 'leave-request.edit', 'label' => 'Edit', 'is_system' => false],
                'delete'  => ['name' => 'leave-request.delete', 'label' => 'Delete', 'is_system' => false],
                'approve' => ['name' => 'leave-request.approve', 'label' => 'Approve / Reject', 'is_system' => false],
            ]],

            'salary-component' => ['label' => 'Salary Components', 'actions' => [
                'view'   => ['name' => 'salary-component.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'salary-component.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'salary-component.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'salary-component.delete', 'label' => 'Delete', 'is_system' => false],
            ]],

            'salary-structure' => ['label' => 'Salary Structures', 'actions' => [
                'view'   => ['name' => 'salary-structure.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'salary-structure.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'salary-structure.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'salary-structure.delete', 'label' => 'Delete', 'is_system' => false],
            ]],

            'payroll' => ['label' => 'Payroll', 'actions' => [
                'view'     => ['name' => 'payroll.view', 'label' => 'View', 'is_system' => false],
                'create'   => ['name' => 'payroll.create', 'label' => 'Generate Run', 'is_system' => false],
                'edit'     => ['name' => 'payroll.edit', 'label' => 'Edit', 'is_system' => false],
                'delete'   => ['name' => 'payroll.delete', 'label' => 'Delete', 'is_system' => false],
                'finalize' => ['name' => 'payroll.finalize', 'label' => 'Finalize', 'is_system' => false],
                'pay'      => ['name' => 'payroll.pay', 'label' => 'Mark Paid', 'is_system' => false],
                'reopen'   => ['name' => 'payroll.reopen', 'label' => 'Reopen', 'is_system' => false],
            ]],

            'payslip' => ['label' => 'Salary Slips', 'actions' => [
                'view'  => ['name' => 'payslip.view', 'label' => 'View', 'is_system' => false],
                'print' => ['name' => 'payslip.print', 'label' => 'Print / Download', 'is_system' => false],
            ]],

            'employee-advance' => ['label' => 'Employee Advances', 'actions' => [
                'view'    => ['name' => 'employee-advance.view', 'label' => 'View', 'is_system' => false],
                'create'  => ['name' => 'employee-advance.create', 'label' => 'Create', 'is_system' => false],
                'edit'    => ['name' => 'employee-advance.edit', 'label' => 'Edit', 'is_system' => false],
                'delete'  => ['name' => 'employee-advance.delete', 'label' => 'Delete', 'is_system' => false],
                'approve' => ['name' => 'employee-advance.approve', 'label' => 'Approve / Reject', 'is_system' => false],
                'import'  => ['name' => 'employee-advance.import', 'label' => 'Import', 'is_system' => false],
                'export'  => ['name' => 'employee-advance.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'employee-deduction' => ['label' => 'Employee Deductions', 'actions' => [
                'view'   => ['name' => 'employee-deduction.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'employee-deduction.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'employee-deduction.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'employee-deduction.delete', 'label' => 'Delete', 'is_system' => false],
            ]],

            'employee-ledger' => ['label' => 'Employee Ledger', 'actions' => [
                'view' => ['name' => 'employee-ledger.view', 'label' => 'View', 'is_system' => false],
            ]],

            'employee-exit' => ['label' => 'Resignation / Termination', 'actions' => [
                'view'     => ['name' => 'employee-exit.view', 'label' => 'View', 'is_system' => false],
                'create'   => ['name' => 'employee-exit.create', 'label' => 'Create', 'is_system' => false],
                'edit'     => ['name' => 'employee-exit.edit', 'label' => 'Edit', 'is_system' => false],
                'approve'  => ['name' => 'employee-exit.approve', 'label' => 'Approve / Reject', 'is_system' => false],
                'finalize' => ['name' => 'employee-exit.finalize', 'label' => 'Finalize', 'is_system' => false],
            ]],

            'employee-clearance' => ['label' => 'Employee Clearance', 'actions' => [
                'view'   => ['name' => 'employee-clearance.view', 'label' => 'View', 'is_system' => false],
                'manage' => ['name' => 'employee-clearance.manage', 'label' => 'Clear / Reject', 'is_system' => false],
            ]],

            'asset' => ['label' => 'Assets', 'actions' => [
                'view'   => ['name' => 'asset.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'asset.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'asset.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'asset.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'asset.status', 'label' => 'Change Status', 'is_system' => false],
                'import' => ['name' => 'asset.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'asset.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'asset-allocation' => ['label' => 'Asset Allocation', 'actions' => [
                'view'   => ['name' => 'asset-allocation.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'asset-allocation.create', 'label' => 'Issue', 'is_system' => false],
                'edit'   => ['name' => 'asset-allocation.edit', 'label' => 'Return / Update', 'is_system' => false],
                'delete' => ['name' => 'asset-allocation.delete', 'label' => 'Delete', 'is_system' => false],
                'import' => ['name' => 'asset-allocation.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'asset-allocation.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'ess' => ['label' => 'Employee Self Service', 'actions' => [
                'dashboard_view'     => ['name' => 'ess.dashboard.view', 'label' => 'View Dashboard', 'is_system' => false],
                'attendance_manage'  => ['name' => 'ess.attendance.manage', 'label' => 'Check In / Check Out', 'is_system' => false],
                'leave_apply'        => ['name' => 'ess.leave.apply', 'label' => 'Apply for Leave', 'is_system' => false],
                'leave_view'         => ['name' => 'ess.leave.view', 'label' => 'View Own Leave History', 'is_system' => false],
                'payslip_view'       => ['name' => 'ess.payslip.view', 'label' => 'View Own Salary Slips', 'is_system' => false],
                'profile_view'       => ['name' => 'ess.profile.view', 'label' => 'View Own Profile', 'is_system' => false],
                'resignation_apply'  => ['name' => 'ess.resignation.apply', 'label' => 'Submit Resignation', 'is_system' => false],
                'advance_apply'      => ['name' => 'ess.advance.apply', 'label' => 'Request Advance', 'is_system' => false],
            ]],

            // ---- HRM & Payroll Reports ----
            // 'hrm-reports' covers organizational/attendance/leave/lifecycle reports
            // (non-financial) - granted to Reporting Analyst as well as HR Manager.
            // 'payroll-reports' covers salary/payroll-run/cost reports (financial) -
            // granted to HR Manager only, deliberately withheld from Reporting Analyst.
            // See CLAUDE.md and RoleDefaultPermissions for the reasoning.

            'hrm-reports' => ['label' => 'HRM Reports', 'actions' => [
                'employee_master' => ['name' => 'reports.employee-master-report.view', 'label' => 'Employee Master Report', 'is_system' => false],
                'employee_master_print' => ['name' => 'reports.employee-master-report.print', 'label' => 'Employee Master Report - Print', 'is_system' => false],
                'employee_master_pdf' => ['name' => 'reports.employee-master-report.pdf', 'label' => 'Employee Master Report - PDF', 'is_system' => false],
                'employee_master_export' => ['name' => 'reports.employee-master-report.export', 'label' => 'Employee Master Report - Export (Excel)', 'is_system' => false],
                'employee_master_export_csv' => ['name' => 'reports.employee-master-report.export-csv', 'label' => 'Employee Master Report - Export (CSV)', 'is_system' => false],
                'employee_directory' => ['name' => 'reports.employee-directory-report.view', 'label' => 'Employee Directory Report', 'is_system' => false],
                'employee_directory_print' => ['name' => 'reports.employee-directory-report.print', 'label' => 'Employee Directory Report - Print', 'is_system' => false],
                'employee_directory_pdf' => ['name' => 'reports.employee-directory-report.pdf', 'label' => 'Employee Directory Report - PDF', 'is_system' => false],
                'employee_directory_export' => ['name' => 'reports.employee-directory-report.export', 'label' => 'Employee Directory Report - Export (Excel)', 'is_system' => false],
                'employee_directory_export_csv' => ['name' => 'reports.employee-directory-report.export-csv', 'label' => 'Employee Directory Report - Export (CSV)', 'is_system' => false],
                'employee_joining' => ['name' => 'reports.employee-joining-report.view', 'label' => 'Employee Joining Report', 'is_system' => false],
                'employee_joining_print' => ['name' => 'reports.employee-joining-report.print', 'label' => 'Employee Joining Report - Print', 'is_system' => false],
                'employee_joining_pdf' => ['name' => 'reports.employee-joining-report.pdf', 'label' => 'Employee Joining Report - PDF', 'is_system' => false],
                'employee_joining_export' => ['name' => 'reports.employee-joining-report.export', 'label' => 'Employee Joining Report - Export (Excel)', 'is_system' => false],
                'employee_joining_export_csv' => ['name' => 'reports.employee-joining-report.export-csv', 'label' => 'Employee Joining Report - Export (CSV)', 'is_system' => false],
                'employee_exit' => ['name' => 'reports.employee-exit-report.view', 'label' => 'Employee Exit Report', 'is_system' => false],
                'employee_exit_print' => ['name' => 'reports.employee-exit-report.print', 'label' => 'Employee Exit Report - Print', 'is_system' => false],
                'employee_exit_pdf' => ['name' => 'reports.employee-exit-report.pdf', 'label' => 'Employee Exit Report - PDF', 'is_system' => false],
                'employee_exit_export' => ['name' => 'reports.employee-exit-report.export', 'label' => 'Employee Exit Report - Export (Excel)', 'is_system' => false],
                'employee_exit_export_csv' => ['name' => 'reports.employee-exit-report.export-csv', 'label' => 'Employee Exit Report - Export (CSV)', 'is_system' => false],
                'department_wise_employee' => ['name' => 'reports.department-wise-employee-report.view', 'label' => 'Department-wise Employee Report', 'is_system' => false],
                'department_wise_employee_print' => ['name' => 'reports.department-wise-employee-report.print', 'label' => 'Department-wise Employee Report - Print', 'is_system' => false],
                'department_wise_employee_pdf' => ['name' => 'reports.department-wise-employee-report.pdf', 'label' => 'Department-wise Employee Report - PDF', 'is_system' => false],
                'department_wise_employee_export' => ['name' => 'reports.department-wise-employee-report.export', 'label' => 'Department-wise Employee Report - Export (Excel)', 'is_system' => false],
                'department_wise_employee_export_csv' => ['name' => 'reports.department-wise-employee-report.export-csv', 'label' => 'Department-wise Employee Report - Export (CSV)', 'is_system' => false],
                'designation_wise_employee' => ['name' => 'reports.designation-wise-employee-report.view', 'label' => 'Designation-wise Employee Report', 'is_system' => false],
                'designation_wise_employee_print' => ['name' => 'reports.designation-wise-employee-report.print', 'label' => 'Designation-wise Employee Report - Print', 'is_system' => false],
                'designation_wise_employee_pdf' => ['name' => 'reports.designation-wise-employee-report.pdf', 'label' => 'Designation-wise Employee Report - PDF', 'is_system' => false],
                'designation_wise_employee_export' => ['name' => 'reports.designation-wise-employee-report.export', 'label' => 'Designation-wise Employee Report - Export (Excel)', 'is_system' => false],
                'designation_wise_employee_export_csv' => ['name' => 'reports.designation-wise-employee-report.export-csv', 'label' => 'Designation-wise Employee Report - Export (CSV)', 'is_system' => false],
                'branch_wise_employee' => ['name' => 'reports.branch-wise-employee-report.view', 'label' => 'Branch-wise Employee Report', 'is_system' => false],
                'branch_wise_employee_print' => ['name' => 'reports.branch-wise-employee-report.print', 'label' => 'Branch-wise Employee Report - Print', 'is_system' => false],
                'branch_wise_employee_pdf' => ['name' => 'reports.branch-wise-employee-report.pdf', 'label' => 'Branch-wise Employee Report - PDF', 'is_system' => false],
                'branch_wise_employee_export' => ['name' => 'reports.branch-wise-employee-report.export', 'label' => 'Branch-wise Employee Report - Export (Excel)', 'is_system' => false],
                'branch_wise_employee_export_csv' => ['name' => 'reports.branch-wise-employee-report.export-csv', 'label' => 'Branch-wise Employee Report - Export (CSV)', 'is_system' => false],
                'employee_status' => ['name' => 'reports.employee-status-report.view', 'label' => 'Employee Status Report', 'is_system' => false],
                'employee_status_print' => ['name' => 'reports.employee-status-report.print', 'label' => 'Employee Status Report - Print', 'is_system' => false],
                'employee_status_pdf' => ['name' => 'reports.employee-status-report.pdf', 'label' => 'Employee Status Report - PDF', 'is_system' => false],
                'employee_status_export' => ['name' => 'reports.employee-status-report.export', 'label' => 'Employee Status Report - Export (Excel)', 'is_system' => false],
                'employee_status_export_csv' => ['name' => 'reports.employee-status-report.export-csv', 'label' => 'Employee Status Report - Export (CSV)', 'is_system' => false],
                'attendance_summary' => ['name' => 'reports.attendance-summary-report.view', 'label' => 'Attendance Summary Report', 'is_system' => false],
                'attendance_summary_print' => ['name' => 'reports.attendance-summary-report.print', 'label' => 'Attendance Summary Report - Print', 'is_system' => false],
                'attendance_summary_pdf' => ['name' => 'reports.attendance-summary-report.pdf', 'label' => 'Attendance Summary Report - PDF', 'is_system' => false],
                'attendance_summary_export' => ['name' => 'reports.attendance-summary-report.export', 'label' => 'Attendance Summary Report - Export (Excel)', 'is_system' => false],
                'attendance_summary_export_csv' => ['name' => 'reports.attendance-summary-report.export-csv', 'label' => 'Attendance Summary Report - Export (CSV)', 'is_system' => false],
                'daily_attendance' => ['name' => 'reports.daily-attendance-report.view', 'label' => 'Daily Attendance Report', 'is_system' => false],
                'daily_attendance_print' => ['name' => 'reports.daily-attendance-report.print', 'label' => 'Daily Attendance Report - Print', 'is_system' => false],
                'daily_attendance_pdf' => ['name' => 'reports.daily-attendance-report.pdf', 'label' => 'Daily Attendance Report - PDF', 'is_system' => false],
                'daily_attendance_export' => ['name' => 'reports.daily-attendance-report.export', 'label' => 'Daily Attendance Report - Export (Excel)', 'is_system' => false],
                'daily_attendance_export_csv' => ['name' => 'reports.daily-attendance-report.export-csv', 'label' => 'Daily Attendance Report - Export (CSV)', 'is_system' => false],
                'monthly_attendance' => ['name' => 'reports.monthly-attendance-report.view', 'label' => 'Monthly Attendance Report', 'is_system' => false],
                'monthly_attendance_print' => ['name' => 'reports.monthly-attendance-report.print', 'label' => 'Monthly Attendance Report - Print', 'is_system' => false],
                'monthly_attendance_pdf' => ['name' => 'reports.monthly-attendance-report.pdf', 'label' => 'Monthly Attendance Report - PDF', 'is_system' => false],
                'monthly_attendance_export' => ['name' => 'reports.monthly-attendance-report.export', 'label' => 'Monthly Attendance Report - Export (Excel)', 'is_system' => false],
                'monthly_attendance_export_csv' => ['name' => 'reports.monthly-attendance-report.export-csv', 'label' => 'Monthly Attendance Report - Export (CSV)', 'is_system' => false],
                'attendance_register' => ['name' => 'reports.attendance-register.view', 'label' => 'Attendance Register', 'is_system' => false],
                'attendance_register_print' => ['name' => 'reports.attendance-register.print', 'label' => 'Attendance Register - Print', 'is_system' => false],
                'attendance_register_pdf' => ['name' => 'reports.attendance-register.pdf', 'label' => 'Attendance Register - PDF', 'is_system' => false],
                'attendance_register_export' => ['name' => 'reports.attendance-register.export', 'label' => 'Attendance Register - Export (Excel)', 'is_system' => false],
                'attendance_register_export_csv' => ['name' => 'reports.attendance-register.export-csv', 'label' => 'Attendance Register - Export (CSV)', 'is_system' => false],
                'late_attendance' => ['name' => 'reports.late-attendance-report.view', 'label' => 'Late Attendance Report', 'is_system' => false],
                'late_attendance_print' => ['name' => 'reports.late-attendance-report.print', 'label' => 'Late Attendance Report - Print', 'is_system' => false],
                'late_attendance_pdf' => ['name' => 'reports.late-attendance-report.pdf', 'label' => 'Late Attendance Report - PDF', 'is_system' => false],
                'late_attendance_export' => ['name' => 'reports.late-attendance-report.export', 'label' => 'Late Attendance Report - Export (Excel)', 'is_system' => false],
                'late_attendance_export_csv' => ['name' => 'reports.late-attendance-report.export-csv', 'label' => 'Late Attendance Report - Export (CSV)', 'is_system' => false],
                'early_checkout' => ['name' => 'reports.early-checkout-report.view', 'label' => 'Early Checkout Report', 'is_system' => false],
                'early_checkout_print' => ['name' => 'reports.early-checkout-report.print', 'label' => 'Early Checkout Report - Print', 'is_system' => false],
                'early_checkout_pdf' => ['name' => 'reports.early-checkout-report.pdf', 'label' => 'Early Checkout Report - PDF', 'is_system' => false],
                'early_checkout_export' => ['name' => 'reports.early-checkout-report.export', 'label' => 'Early Checkout Report - Export (Excel)', 'is_system' => false],
                'early_checkout_export_csv' => ['name' => 'reports.early-checkout-report.export-csv', 'label' => 'Early Checkout Report - Export (CSV)', 'is_system' => false],
                'absent_employees' => ['name' => 'reports.absent-employees-report.view', 'label' => 'Absent Employees Report', 'is_system' => false],
                'absent_employees_print' => ['name' => 'reports.absent-employees-report.print', 'label' => 'Absent Employees Report - Print', 'is_system' => false],
                'absent_employees_pdf' => ['name' => 'reports.absent-employees-report.pdf', 'label' => 'Absent Employees Report - PDF', 'is_system' => false],
                'absent_employees_export' => ['name' => 'reports.absent-employees-report.export', 'label' => 'Absent Employees Report - Export (Excel)', 'is_system' => false],
                'absent_employees_export_csv' => ['name' => 'reports.absent-employees-report.export-csv', 'label' => 'Absent Employees Report - Export (CSV)', 'is_system' => false],
                'missing_checkin_checkout' => ['name' => 'reports.missing-checkin-checkout-report.view', 'label' => 'Missing Check-In/Check-Out Report', 'is_system' => false],
                'missing_checkin_checkout_print' => ['name' => 'reports.missing-checkin-checkout-report.print', 'label' => 'Missing Check-In/Check-Out Report - Print', 'is_system' => false],
                'missing_checkin_checkout_pdf' => ['name' => 'reports.missing-checkin-checkout-report.pdf', 'label' => 'Missing Check-In/Check-Out Report - PDF', 'is_system' => false],
                'missing_checkin_checkout_export' => ['name' => 'reports.missing-checkin-checkout-report.export', 'label' => 'Missing Check-In/Check-Out Report - Export (Excel)', 'is_system' => false],
                'missing_checkin_checkout_export_csv' => ['name' => 'reports.missing-checkin-checkout-report.export-csv', 'label' => 'Missing Check-In/Check-Out Report - Export (CSV)', 'is_system' => false],
                'overtime' => ['name' => 'reports.overtime-report.view', 'label' => 'Overtime Report', 'is_system' => false],
                'overtime_print' => ['name' => 'reports.overtime-report.print', 'label' => 'Overtime Report - Print', 'is_system' => false],
                'overtime_pdf' => ['name' => 'reports.overtime-report.pdf', 'label' => 'Overtime Report - PDF', 'is_system' => false],
                'overtime_export' => ['name' => 'reports.overtime-report.export', 'label' => 'Overtime Report - Export (Excel)', 'is_system' => false],
                'overtime_export_csv' => ['name' => 'reports.overtime-report.export-csv', 'label' => 'Overtime Report - Export (CSV)', 'is_system' => false],
                'shift_wise_attendance' => ['name' => 'reports.shift-wise-attendance-report.view', 'label' => 'Shift-wise Attendance Report', 'is_system' => false],
                'shift_wise_attendance_print' => ['name' => 'reports.shift-wise-attendance-report.print', 'label' => 'Shift-wise Attendance Report - Print', 'is_system' => false],
                'shift_wise_attendance_pdf' => ['name' => 'reports.shift-wise-attendance-report.pdf', 'label' => 'Shift-wise Attendance Report - PDF', 'is_system' => false],
                'shift_wise_attendance_export' => ['name' => 'reports.shift-wise-attendance-report.export', 'label' => 'Shift-wise Attendance Report - Export (Excel)', 'is_system' => false],
                'shift_wise_attendance_export_csv' => ['name' => 'reports.shift-wise-attendance-report.export-csv', 'label' => 'Shift-wise Attendance Report - Export (CSV)', 'is_system' => false],
                'shift_assignment' => ['name' => 'reports.shift-assignment-report.view', 'label' => 'Shift Assignment Report', 'is_system' => false],
                'shift_assignment_print' => ['name' => 'reports.shift-assignment-report.print', 'label' => 'Shift Assignment Report - Print', 'is_system' => false],
                'shift_assignment_pdf' => ['name' => 'reports.shift-assignment-report.pdf', 'label' => 'Shift Assignment Report - PDF', 'is_system' => false],
                'shift_assignment_export' => ['name' => 'reports.shift-assignment-report.export', 'label' => 'Shift Assignment Report - Export (Excel)', 'is_system' => false],
                'shift_assignment_export_csv' => ['name' => 'reports.shift-assignment-report.export-csv', 'label' => 'Shift Assignment Report - Export (CSV)', 'is_system' => false],
                'leave_summary' => ['name' => 'reports.leave-summary-report.view', 'label' => 'Leave Summary Report', 'is_system' => false],
                'leave_summary_print' => ['name' => 'reports.leave-summary-report.print', 'label' => 'Leave Summary Report - Print', 'is_system' => false],
                'leave_summary_pdf' => ['name' => 'reports.leave-summary-report.pdf', 'label' => 'Leave Summary Report - PDF', 'is_system' => false],
                'leave_summary_export' => ['name' => 'reports.leave-summary-report.export', 'label' => 'Leave Summary Report - Export (Excel)', 'is_system' => false],
                'leave_summary_export_csv' => ['name' => 'reports.leave-summary-report.export-csv', 'label' => 'Leave Summary Report - Export (CSV)', 'is_system' => false],
                'employee_leave_history' => ['name' => 'reports.employee-leave-history-report.view', 'label' => 'Employee Leave History Report', 'is_system' => false],
                'employee_leave_history_print' => ['name' => 'reports.employee-leave-history-report.print', 'label' => 'Employee Leave History Report - Print', 'is_system' => false],
                'employee_leave_history_pdf' => ['name' => 'reports.employee-leave-history-report.pdf', 'label' => 'Employee Leave History Report - PDF', 'is_system' => false],
                'employee_leave_history_export' => ['name' => 'reports.employee-leave-history-report.export', 'label' => 'Employee Leave History Report - Export (Excel)', 'is_system' => false],
                'employee_leave_history_export_csv' => ['name' => 'reports.employee-leave-history-report.export-csv', 'label' => 'Employee Leave History Report - Export (CSV)', 'is_system' => false],
                'leave_type_wise' => ['name' => 'reports.leave-type-wise-report.view', 'label' => 'Leave Type-wise Report', 'is_system' => false],
                'leave_type_wise_print' => ['name' => 'reports.leave-type-wise-report.print', 'label' => 'Leave Type-wise Report - Print', 'is_system' => false],
                'leave_type_wise_pdf' => ['name' => 'reports.leave-type-wise-report.pdf', 'label' => 'Leave Type-wise Report - PDF', 'is_system' => false],
                'leave_type_wise_export' => ['name' => 'reports.leave-type-wise-report.export', 'label' => 'Leave Type-wise Report - Export (Excel)', 'is_system' => false],
                'leave_type_wise_export_csv' => ['name' => 'reports.leave-type-wise-report.export-csv', 'label' => 'Leave Type-wise Report - Export (CSV)', 'is_system' => false],
                'department_wise_leave' => ['name' => 'reports.department-wise-leave-report.view', 'label' => 'Department-wise Leave Report', 'is_system' => false],
                'department_wise_leave_print' => ['name' => 'reports.department-wise-leave-report.print', 'label' => 'Department-wise Leave Report - Print', 'is_system' => false],
                'department_wise_leave_pdf' => ['name' => 'reports.department-wise-leave-report.pdf', 'label' => 'Department-wise Leave Report - PDF', 'is_system' => false],
                'department_wise_leave_export' => ['name' => 'reports.department-wise-leave-report.export', 'label' => 'Department-wise Leave Report - Export (Excel)', 'is_system' => false],
                'department_wise_leave_export_csv' => ['name' => 'reports.department-wise-leave-report.export-csv', 'label' => 'Department-wise Leave Report - Export (CSV)', 'is_system' => false],
                'pending_leave_approval' => ['name' => 'reports.pending-leave-approval-report.view', 'label' => 'Pending Leave Approval Report', 'is_system' => false],
                'pending_leave_approval_print' => ['name' => 'reports.pending-leave-approval-report.print', 'label' => 'Pending Leave Approval Report - Print', 'is_system' => false],
                'pending_leave_approval_pdf' => ['name' => 'reports.pending-leave-approval-report.pdf', 'label' => 'Pending Leave Approval Report - PDF', 'is_system' => false],
                'pending_leave_approval_export' => ['name' => 'reports.pending-leave-approval-report.export', 'label' => 'Pending Leave Approval Report - Export (Excel)', 'is_system' => false],
                'pending_leave_approval_export_csv' => ['name' => 'reports.pending-leave-approval-report.export-csv', 'label' => 'Pending Leave Approval Report - Export (CSV)', 'is_system' => false],
                'leave_approval_status' => ['name' => 'reports.leave-approval-status-report.view', 'label' => 'Leave Approval Status Report', 'is_system' => false],
                'leave_approval_status_print' => ['name' => 'reports.leave-approval-status-report.print', 'label' => 'Leave Approval Status Report - Print', 'is_system' => false],
                'leave_approval_status_pdf' => ['name' => 'reports.leave-approval-status-report.pdf', 'label' => 'Leave Approval Status Report - PDF', 'is_system' => false],
                'leave_approval_status_export' => ['name' => 'reports.leave-approval-status-report.export', 'label' => 'Leave Approval Status Report - Export (Excel)', 'is_system' => false],
                'leave_approval_status_export_csv' => ['name' => 'reports.leave-approval-status-report.export-csv', 'label' => 'Leave Approval Status Report - Export (CSV)', 'is_system' => false],
                'leave_balance' => ['name' => 'reports.leave-balance-report.view', 'label' => 'Leave Balance Report', 'is_system' => false],
                'leave_balance_print' => ['name' => 'reports.leave-balance-report.print', 'label' => 'Leave Balance Report - Print', 'is_system' => false],
                'leave_balance_pdf' => ['name' => 'reports.leave-balance-report.pdf', 'label' => 'Leave Balance Report - PDF', 'is_system' => false],
                'leave_balance_export' => ['name' => 'reports.leave-balance-report.export', 'label' => 'Leave Balance Report - Export (Excel)', 'is_system' => false],
                'leave_balance_export_csv' => ['name' => 'reports.leave-balance-report.export-csv', 'label' => 'Leave Balance Report - Export (CSV)', 'is_system' => false],
                'salary_structure' => ['name' => 'reports.salary-structure-report.view', 'label' => 'Salary Structure Report', 'is_system' => false],
                'salary_structure_print' => ['name' => 'reports.salary-structure-report.print', 'label' => 'Salary Structure Report - Print', 'is_system' => false],
                'salary_structure_pdf' => ['name' => 'reports.salary-structure-report.pdf', 'label' => 'Salary Structure Report - PDF', 'is_system' => false],
                'salary_structure_export' => ['name' => 'reports.salary-structure-report.export', 'label' => 'Salary Structure Report - Export (Excel)', 'is_system' => false],
                'salary_structure_export_csv' => ['name' => 'reports.salary-structure-report.export-csv', 'label' => 'Salary Structure Report - Export (CSV)', 'is_system' => false],
                'salary_component' => ['name' => 'reports.salary-component-report.view', 'label' => 'Salary Component Report', 'is_system' => false],
                'salary_component_print' => ['name' => 'reports.salary-component-report.print', 'label' => 'Salary Component Report - Print', 'is_system' => false],
                'salary_component_pdf' => ['name' => 'reports.salary-component-report.pdf', 'label' => 'Salary Component Report - PDF', 'is_system' => false],
                'salary_component_export' => ['name' => 'reports.salary-component-report.export', 'label' => 'Salary Component Report - Export (Excel)', 'is_system' => false],
                'salary_component_export_csv' => ['name' => 'reports.salary-component-report.export-csv', 'label' => 'Salary Component Report - Export (CSV)', 'is_system' => false],
                'deduction' => ['name' => 'reports.deduction-report.view', 'label' => 'Deduction Report', 'is_system' => false],
                'deduction_print' => ['name' => 'reports.deduction-report.print', 'label' => 'Deduction Report - Print', 'is_system' => false],
                'deduction_pdf' => ['name' => 'reports.deduction-report.pdf', 'label' => 'Deduction Report - PDF', 'is_system' => false],
                'deduction_export' => ['name' => 'reports.deduction-report.export', 'label' => 'Deduction Report - Export (Excel)', 'is_system' => false],
                'deduction_export_csv' => ['name' => 'reports.deduction-report.export-csv', 'label' => 'Deduction Report - Export (CSV)', 'is_system' => false],
                'employee_advance' => ['name' => 'reports.employee-advance-report.view', 'label' => 'Employee Advance Report', 'is_system' => false],
                'employee_advance_print' => ['name' => 'reports.employee-advance-report.print', 'label' => 'Employee Advance Report - Print', 'is_system' => false],
                'employee_advance_pdf' => ['name' => 'reports.employee-advance-report.pdf', 'label' => 'Employee Advance Report - PDF', 'is_system' => false],
                'employee_advance_export' => ['name' => 'reports.employee-advance-report.export', 'label' => 'Employee Advance Report - Export (Excel)', 'is_system' => false],
                'employee_advance_export_csv' => ['name' => 'reports.employee-advance-report.export-csv', 'label' => 'Employee Advance Report - Export (CSV)', 'is_system' => false],
                'advance_recovery' => ['name' => 'reports.advance-recovery-report.view', 'label' => 'Advance Recovery Report', 'is_system' => false],
                'advance_recovery_print' => ['name' => 'reports.advance-recovery-report.print', 'label' => 'Advance Recovery Report - Print', 'is_system' => false],
                'advance_recovery_pdf' => ['name' => 'reports.advance-recovery-report.pdf', 'label' => 'Advance Recovery Report - PDF', 'is_system' => false],
                'advance_recovery_export' => ['name' => 'reports.advance-recovery-report.export', 'label' => 'Advance Recovery Report - Export (Excel)', 'is_system' => false],
                'advance_recovery_export_csv' => ['name' => 'reports.advance-recovery-report.export-csv', 'label' => 'Advance Recovery Report - Export (CSV)', 'is_system' => false],
                'employee_ledger' => ['name' => 'reports.employee-ledger-report.view', 'label' => 'Employee Ledger Report', 'is_system' => false],
                'employee_ledger_print' => ['name' => 'reports.employee-ledger-report.print', 'label' => 'Employee Ledger Report - Print', 'is_system' => false],
                'employee_ledger_pdf' => ['name' => 'reports.employee-ledger-report.pdf', 'label' => 'Employee Ledger Report - PDF', 'is_system' => false],
                'employee_ledger_export' => ['name' => 'reports.employee-ledger-report.export', 'label' => 'Employee Ledger Report - Export (Excel)', 'is_system' => false],
                'employee_ledger_export_csv' => ['name' => 'reports.employee-ledger-report.export-csv', 'label' => 'Employee Ledger Report - Export (CSV)', 'is_system' => false],
                'resignation' => ['name' => 'reports.resignation-report.view', 'label' => 'Resignation Report', 'is_system' => false],
                'resignation_print' => ['name' => 'reports.resignation-report.print', 'label' => 'Resignation Report - Print', 'is_system' => false],
                'resignation_pdf' => ['name' => 'reports.resignation-report.pdf', 'label' => 'Resignation Report - PDF', 'is_system' => false],
                'resignation_export' => ['name' => 'reports.resignation-report.export', 'label' => 'Resignation Report - Export (Excel)', 'is_system' => false],
                'resignation_export_csv' => ['name' => 'reports.resignation-report.export-csv', 'label' => 'Resignation Report - Export (CSV)', 'is_system' => false],
                'termination' => ['name' => 'reports.termination-report.view', 'label' => 'Termination Report', 'is_system' => false],
                'termination_print' => ['name' => 'reports.termination-report.print', 'label' => 'Termination Report - Print', 'is_system' => false],
                'termination_pdf' => ['name' => 'reports.termination-report.pdf', 'label' => 'Termination Report - PDF', 'is_system' => false],
                'termination_export' => ['name' => 'reports.termination-report.export', 'label' => 'Termination Report - Export (Excel)', 'is_system' => false],
                'termination_export_csv' => ['name' => 'reports.termination-report.export-csv', 'label' => 'Termination Report - Export (CSV)', 'is_system' => false],
                'employee_clearance' => ['name' => 'reports.employee-clearance-report.view', 'label' => 'Employee Clearance Report', 'is_system' => false],
                'employee_clearance_print' => ['name' => 'reports.employee-clearance-report.print', 'label' => 'Employee Clearance Report - Print', 'is_system' => false],
                'employee_clearance_pdf' => ['name' => 'reports.employee-clearance-report.pdf', 'label' => 'Employee Clearance Report - PDF', 'is_system' => false],
                'employee_clearance_export' => ['name' => 'reports.employee-clearance-report.export', 'label' => 'Employee Clearance Report - Export (Excel)', 'is_system' => false],
                'employee_clearance_export_csv' => ['name' => 'reports.employee-clearance-report.export-csv', 'label' => 'Employee Clearance Report - Export (CSV)', 'is_system' => false],
                'asset_allocation' => ['name' => 'reports.asset-allocation-report.view', 'label' => 'Asset Allocation Report', 'is_system' => false],
                'asset_allocation_print' => ['name' => 'reports.asset-allocation-report.print', 'label' => 'Asset Allocation Report - Print', 'is_system' => false],
                'asset_allocation_pdf' => ['name' => 'reports.asset-allocation-report.pdf', 'label' => 'Asset Allocation Report - PDF', 'is_system' => false],
                'asset_allocation_export' => ['name' => 'reports.asset-allocation-report.export', 'label' => 'Asset Allocation Report - Export (Excel)', 'is_system' => false],
                'asset_allocation_export_csv' => ['name' => 'reports.asset-allocation-report.export-csv', 'label' => 'Asset Allocation Report - Export (CSV)', 'is_system' => false],
                'employee_asset_return' => ['name' => 'reports.employee-asset-return-report.view', 'label' => 'Employee Asset Return Report', 'is_system' => false],
                'employee_asset_return_print' => ['name' => 'reports.employee-asset-return-report.print', 'label' => 'Employee Asset Return Report - Print', 'is_system' => false],
                'employee_asset_return_pdf' => ['name' => 'reports.employee-asset-return-report.pdf', 'label' => 'Employee Asset Return Report - PDF', 'is_system' => false],
                'employee_asset_return_export' => ['name' => 'reports.employee-asset-return-report.export', 'label' => 'Employee Asset Return Report - Export (Excel)', 'is_system' => false],
                'employee_asset_return_export_csv' => ['name' => 'reports.employee-asset-return-report.export-csv', 'label' => 'Employee Asset Return Report - Export (CSV)', 'is_system' => false],
                'employee_document' => ['name' => 'reports.employee-document-report.view', 'label' => 'Employee Document Report', 'is_system' => false],
                'employee_document_print' => ['name' => 'reports.employee-document-report.print', 'label' => 'Employee Document Report - Print', 'is_system' => false],
                'employee_document_pdf' => ['name' => 'reports.employee-document-report.pdf', 'label' => 'Employee Document Report - PDF', 'is_system' => false],
                'employee_document_export' => ['name' => 'reports.employee-document-report.export', 'label' => 'Employee Document Report - Export (Excel)', 'is_system' => false],
                'employee_document_export_csv' => ['name' => 'reports.employee-document-report.export-csv', 'label' => 'Employee Document Report - Export (CSV)', 'is_system' => false],
                'employee_lifecycle' => ['name' => 'reports.employee-lifecycle-report.view', 'label' => 'Employee Lifecycle Report', 'is_system' => false],
                'employee_lifecycle_print' => ['name' => 'reports.employee-lifecycle-report.print', 'label' => 'Employee Lifecycle Report - Print', 'is_system' => false],
                'employee_lifecycle_pdf' => ['name' => 'reports.employee-lifecycle-report.pdf', 'label' => 'Employee Lifecycle Report - PDF', 'is_system' => false],
                'employee_lifecycle_export' => ['name' => 'reports.employee-lifecycle-report.export', 'label' => 'Employee Lifecycle Report - Export (Excel)', 'is_system' => false],
                'employee_lifecycle_export_csv' => ['name' => 'reports.employee-lifecycle-report.export-csv', 'label' => 'Employee Lifecycle Report - Export (CSV)', 'is_system' => false],
                'hr_dashboard' => ['name' => 'reports.hr-dashboard-report.view', 'label' => 'HR Dashboard Report', 'is_system' => false],
            ]],

            'payroll-reports' => ['label' => 'Payroll Reports', 'actions' => [
                'payroll_summary' => ['name' => 'reports.payroll-summary-report.view', 'label' => 'Payroll Summary Report', 'is_system' => false],
                'payroll_summary_print' => ['name' => 'reports.payroll-summary-report.print', 'label' => 'Payroll Summary Report - Print', 'is_system' => false],
                'payroll_summary_pdf' => ['name' => 'reports.payroll-summary-report.pdf', 'label' => 'Payroll Summary Report - PDF', 'is_system' => false],
                'payroll_summary_export' => ['name' => 'reports.payroll-summary-report.export', 'label' => 'Payroll Summary Report - Export (Excel)', 'is_system' => false],
                'payroll_summary_export_csv' => ['name' => 'reports.payroll-summary-report.export-csv', 'label' => 'Payroll Summary Report - Export (CSV)', 'is_system' => false],
                'employee_wise_payroll' => ['name' => 'reports.employee-wise-payroll-report.view', 'label' => 'Employee-wise Payroll Report', 'is_system' => false],
                'employee_wise_payroll_print' => ['name' => 'reports.employee-wise-payroll-report.print', 'label' => 'Employee-wise Payroll Report - Print', 'is_system' => false],
                'employee_wise_payroll_pdf' => ['name' => 'reports.employee-wise-payroll-report.pdf', 'label' => 'Employee-wise Payroll Report - PDF', 'is_system' => false],
                'employee_wise_payroll_export' => ['name' => 'reports.employee-wise-payroll-report.export', 'label' => 'Employee-wise Payroll Report - Export (Excel)', 'is_system' => false],
                'employee_wise_payroll_export_csv' => ['name' => 'reports.employee-wise-payroll-report.export-csv', 'label' => 'Employee-wise Payroll Report - Export (CSV)', 'is_system' => false],
                'department_wise_payroll' => ['name' => 'reports.department-wise-payroll-report.view', 'label' => 'Department-wise Payroll Report', 'is_system' => false],
                'department_wise_payroll_print' => ['name' => 'reports.department-wise-payroll-report.print', 'label' => 'Department-wise Payroll Report - Print', 'is_system' => false],
                'department_wise_payroll_pdf' => ['name' => 'reports.department-wise-payroll-report.pdf', 'label' => 'Department-wise Payroll Report - PDF', 'is_system' => false],
                'department_wise_payroll_export' => ['name' => 'reports.department-wise-payroll-report.export', 'label' => 'Department-wise Payroll Report - Export (Excel)', 'is_system' => false],
                'department_wise_payroll_export_csv' => ['name' => 'reports.department-wise-payroll-report.export-csv', 'label' => 'Department-wise Payroll Report - Export (CSV)', 'is_system' => false],
                'branch_wise_payroll' => ['name' => 'reports.branch-wise-payroll-report.view', 'label' => 'Branch-wise Payroll Report', 'is_system' => false],
                'branch_wise_payroll_print' => ['name' => 'reports.branch-wise-payroll-report.print', 'label' => 'Branch-wise Payroll Report - Print', 'is_system' => false],
                'branch_wise_payroll_pdf' => ['name' => 'reports.branch-wise-payroll-report.pdf', 'label' => 'Branch-wise Payroll Report - PDF', 'is_system' => false],
                'branch_wise_payroll_export' => ['name' => 'reports.branch-wise-payroll-report.export', 'label' => 'Branch-wise Payroll Report - Export (Excel)', 'is_system' => false],
                'branch_wise_payroll_export_csv' => ['name' => 'reports.branch-wise-payroll-report.export-csv', 'label' => 'Branch-wise Payroll Report - Export (CSV)', 'is_system' => false],
                'monthly_payroll_register' => ['name' => 'reports.monthly-payroll-register.view', 'label' => 'Monthly Payroll Register', 'is_system' => false],
                'monthly_payroll_register_print' => ['name' => 'reports.monthly-payroll-register.print', 'label' => 'Monthly Payroll Register - Print', 'is_system' => false],
                'monthly_payroll_register_pdf' => ['name' => 'reports.monthly-payroll-register.pdf', 'label' => 'Monthly Payroll Register - PDF', 'is_system' => false],
                'monthly_payroll_register_export' => ['name' => 'reports.monthly-payroll-register.export', 'label' => 'Monthly Payroll Register - Export (Excel)', 'is_system' => false],
                'monthly_payroll_register_export_csv' => ['name' => 'reports.monthly-payroll-register.export-csv', 'label' => 'Monthly Payroll Register - Export (CSV)', 'is_system' => false],
                'payroll_cost' => ['name' => 'reports.payroll-cost-report.view', 'label' => 'Payroll Cost Report', 'is_system' => false],
                'payroll_cost_print' => ['name' => 'reports.payroll-cost-report.print', 'label' => 'Payroll Cost Report - Print', 'is_system' => false],
                'payroll_cost_pdf' => ['name' => 'reports.payroll-cost-report.pdf', 'label' => 'Payroll Cost Report - PDF', 'is_system' => false],
                'payroll_cost_export' => ['name' => 'reports.payroll-cost-report.export', 'label' => 'Payroll Cost Report - Export (Excel)', 'is_system' => false],
                'payroll_cost_export_csv' => ['name' => 'reports.payroll-cost-report.export-csv', 'label' => 'Payroll Cost Report - Export (CSV)', 'is_system' => false],
                'pending_payroll' => ['name' => 'reports.pending-payroll-report.view', 'label' => 'Pending Payroll Report', 'is_system' => false],
                'pending_payroll_print' => ['name' => 'reports.pending-payroll-report.print', 'label' => 'Pending Payroll Report - Print', 'is_system' => false],
                'pending_payroll_pdf' => ['name' => 'reports.pending-payroll-report.pdf', 'label' => 'Pending Payroll Report - PDF', 'is_system' => false],
                'pending_payroll_export' => ['name' => 'reports.pending-payroll-report.export', 'label' => 'Pending Payroll Report - Export (Excel)', 'is_system' => false],
                'pending_payroll_export_csv' => ['name' => 'reports.pending-payroll-report.export-csv', 'label' => 'Pending Payroll Report - Export (CSV)', 'is_system' => false],
                'salary_slip' => ['name' => 'reports.salary-slip-report.view', 'label' => 'Salary Slip Report', 'is_system' => false],
                'salary_slip_print' => ['name' => 'reports.salary-slip-report.print', 'label' => 'Salary Slip Report - Print', 'is_system' => false],
                'salary_slip_pdf' => ['name' => 'reports.salary-slip-report.pdf', 'label' => 'Salary Slip Report - PDF', 'is_system' => false],
                'salary_slip_export' => ['name' => 'reports.salary-slip-report.export', 'label' => 'Salary Slip Report - Export (Excel)', 'is_system' => false],
                'salary_slip_export_csv' => ['name' => 'reports.salary-slip-report.export-csv', 'label' => 'Salary Slip Report - Export (CSV)', 'is_system' => false],
                'payroll_disbursement' => ['name' => 'reports.payroll-disbursement-report.view', 'label' => 'Payroll Payment/Disbursement Report', 'is_system' => false],
                'payroll_disbursement_print' => ['name' => 'reports.payroll-disbursement-report.print', 'label' => 'Payroll Payment/Disbursement Report - Print', 'is_system' => false],
                'payroll_disbursement_pdf' => ['name' => 'reports.payroll-disbursement-report.pdf', 'label' => 'Payroll Payment/Disbursement Report - PDF', 'is_system' => false],
                'payroll_disbursement_export' => ['name' => 'reports.payroll-disbursement-report.export', 'label' => 'Payroll Payment/Disbursement Report - Export (Excel)', 'is_system' => false],
                'payroll_disbursement_export_csv' => ['name' => 'reports.payroll-disbursement-report.export-csv', 'label' => 'Payroll Payment/Disbursement Report - Export (CSV)', 'is_system' => false],
                'attendance_payroll_comparison' => ['name' => 'reports.attendance-payroll-comparison-report.view', 'label' => 'Employee Attendance & Payroll Comparison Report', 'is_system' => false],
                'attendance_payroll_comparison_print' => ['name' => 'reports.attendance-payroll-comparison-report.print', 'label' => 'Employee Attendance & Payroll Comparison Report - Print', 'is_system' => false],
                'attendance_payroll_comparison_pdf' => ['name' => 'reports.attendance-payroll-comparison-report.pdf', 'label' => 'Employee Attendance & Payroll Comparison Report - PDF', 'is_system' => false],
                'attendance_payroll_comparison_export' => ['name' => 'reports.attendance-payroll-comparison-report.export', 'label' => 'Employee Attendance & Payroll Comparison Report - Export (Excel)', 'is_system' => false],
                'attendance_payroll_comparison_export_csv' => ['name' => 'reports.attendance-payroll-comparison-report.export-csv', 'label' => 'Employee Attendance & Payroll Comparison Report - Export (CSV)', 'is_system' => false],
                'leave_payroll_impact' => ['name' => 'reports.leave-payroll-impact-report.view', 'label' => 'Leave & Payroll Impact Report', 'is_system' => false],
                'leave_payroll_impact_print' => ['name' => 'reports.leave-payroll-impact-report.print', 'label' => 'Leave & Payroll Impact Report - Print', 'is_system' => false],
                'leave_payroll_impact_pdf' => ['name' => 'reports.leave-payroll-impact-report.pdf', 'label' => 'Leave & Payroll Impact Report - PDF', 'is_system' => false],
                'leave_payroll_impact_export' => ['name' => 'reports.leave-payroll-impact-report.export', 'label' => 'Leave & Payroll Impact Report - Export (Excel)', 'is_system' => false],
                'leave_payroll_impact_export_csv' => ['name' => 'reports.leave-payroll-impact-report.export-csv', 'label' => 'Leave & Payroll Impact Report - Export (CSV)', 'is_system' => false],
                'employee_cost' => ['name' => 'reports.employee-cost-report.view', 'label' => 'Employee Cost Report', 'is_system' => false],
                'employee_cost_print' => ['name' => 'reports.employee-cost-report.print', 'label' => 'Employee Cost Report - Print', 'is_system' => false],
                'employee_cost_pdf' => ['name' => 'reports.employee-cost-report.pdf', 'label' => 'Employee Cost Report - PDF', 'is_system' => false],
                'employee_cost_export' => ['name' => 'reports.employee-cost-report.export', 'label' => 'Employee Cost Report - Export (Excel)', 'is_system' => false],
                'employee_cost_export_csv' => ['name' => 'reports.employee-cost-report.export-csv', 'label' => 'Employee Cost Report - Export (CSV)', 'is_system' => false],
                'department_payroll_cost' => ['name' => 'reports.department-payroll-cost-report.view', 'label' => 'Department Payroll Cost Report', 'is_system' => false],
                'department_payroll_cost_print' => ['name' => 'reports.department-payroll-cost-report.print', 'label' => 'Department Payroll Cost Report - Print', 'is_system' => false],
                'department_payroll_cost_pdf' => ['name' => 'reports.department-payroll-cost-report.pdf', 'label' => 'Department Payroll Cost Report - PDF', 'is_system' => false],
                'department_payroll_cost_export' => ['name' => 'reports.department-payroll-cost-report.export', 'label' => 'Department Payroll Cost Report - Export (Excel)', 'is_system' => false],
                'department_payroll_cost_export_csv' => ['name' => 'reports.department-payroll-cost-report.export-csv', 'label' => 'Department Payroll Cost Report - Export (CSV)', 'is_system' => false],
                'branch_payroll_cost' => ['name' => 'reports.branch-payroll-cost-report.view', 'label' => 'Branch Payroll Cost Report', 'is_system' => false],
                'branch_payroll_cost_print' => ['name' => 'reports.branch-payroll-cost-report.print', 'label' => 'Branch Payroll Cost Report - Print', 'is_system' => false],
                'branch_payroll_cost_pdf' => ['name' => 'reports.branch-payroll-cost-report.pdf', 'label' => 'Branch Payroll Cost Report - PDF', 'is_system' => false],
                'branch_payroll_cost_export' => ['name' => 'reports.branch-payroll-cost-report.export', 'label' => 'Branch Payroll Cost Report - Export (Excel)', 'is_system' => false],
                'branch_payroll_cost_export_csv' => ['name' => 'reports.branch-payroll-cost-report.export-csv', 'label' => 'Branch Payroll Cost Report - Export (CSV)', 'is_system' => false],
            ]],

            // ---- Already-seeded modules (existing permission names, kept exactly as-is) ----

            'pos' => ['label' => 'POS', 'actions' => [
                'access'          => ['name' => 'pos.access', 'label' => 'Access POS', 'is_system' => false],
                'register_close'  => ['name' => 'pos.register.close', 'label' => 'Close Register', 'is_system' => false],
                'register_report' => ['name' => 'pos.register.report.view', 'label' => 'View Register Report', 'is_system' => false],
            ]],

            'order' => ['label' => 'Orders', 'actions' => [
                'create'          => ['name' => 'order.create', 'label' => 'Create', 'is_system' => false],
                'edit'            => ['name' => 'order.edit', 'label' => 'Edit', 'is_system' => false],
                'discount_apply'  => ['name' => 'order.discount.apply', 'label' => 'Apply Discount', 'is_system' => false],
                'coupon_apply'    => ['name' => 'order.coupon.apply', 'label' => 'Apply Coupon', 'is_system' => false],
                'price_change'    => ['name' => 'order.price.change', 'label' => 'Change Price', 'is_system' => false],
                'price_override_minimum' => ['name' => 'order.price.override-minimum', 'label' => 'Override Minimum Selling Price', 'is_system' => false],
                'hold'            => ['name' => 'order.hold', 'label' => 'Hold', 'is_system' => false],
                // 'order.cancel_void' is superseded by the separate 'cancel'/'void'
                // actions below (cancel a draft vs. void a posted, accounted sale
                // are different risk levels) - kept declared, never referenced by
                // any route/controller going forward, per the permission-name
                // immutability rule (names are never renamed/removed once shipped).
                'cancel_void'     => ['name' => 'order.cancel_void', 'label' => 'Cancel / Void', 'is_system' => false],
                'cancel'          => ['name' => 'order.cancel', 'label' => 'Cancel', 'is_system' => false],
                'void'            => ['name' => 'order.void', 'label' => 'Void', 'is_system' => false],
                'delete'          => ['name' => 'order.delete', 'label' => 'Delete', 'is_system' => false],
                'refund_process'  => ['name' => 'order.refund.process', 'label' => 'Process Refund', 'is_system' => false],
                'payment_credit'  => ['name' => 'order.payment.credit', 'label' => 'Credit Payment', 'is_system' => false],
                'customer_change' => ['name' => 'order.customer.change', 'label' => 'Change Customer', 'is_system' => false],
                'reopen'          => ['name' => 'order.reopen', 'label' => 'Reopen', 'is_system' => false],
                'export'          => ['name' => 'order.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'order-return' => ['label' => 'Order Returns', 'actions' => [
                'view'    => ['name' => 'order-return.view', 'label' => 'View', 'is_system' => false],
                'create'  => ['name' => 'order-return.create', 'label' => 'Create', 'is_system' => false],
                'edit'    => ['name' => 'order-return.edit', 'label' => 'Edit', 'is_system' => false],
                'delete'  => ['name' => 'order-return.delete', 'label' => 'Delete', 'is_system' => false],
                'approve' => ['name' => 'order-return.approve', 'label' => 'Approve', 'is_system' => false],
                'print'   => ['name' => 'order-return.print', 'label' => 'Print', 'is_system' => false],
            ]],

            'expense' => ['label' => 'Expense (POS)', 'actions' => [
                'access'      => ['name' => 'expense.access', 'label' => 'Access', 'is_system' => false],
                'view'        => ['name' => 'expense.view', 'label' => 'View', 'is_system' => false],
                'create'      => ['name' => 'expense.create', 'label' => 'Create', 'is_system' => false],
                'edit'        => ['name' => 'expense.edit', 'label' => 'Edit', 'is_system' => false],
                'post'        => ['name' => 'expense.post', 'label' => 'Post', 'is_system' => false],
                'delete'      => ['name' => 'expense.delete', 'label' => 'Delete', 'is_system' => false],
                'report_view' => ['name' => 'expense.report.view', 'label' => 'View Report', 'is_system' => false],
                'import'      => ['name' => 'expense.import', 'label' => 'Import', 'is_system' => false],
                'export'      => ['name' => 'expense.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'expense-category' => ['label' => 'Expense Category', 'actions' => [
                'manage' => ['name' => 'expense-category.manage', 'label' => 'Manage', 'is_system' => false],
                'import' => ['name' => 'expense-category.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'expense-category.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'admin-expense' => ['label' => 'Admin Expenses', 'actions' => [
                'manage' => ['name' => 'admin-expense.manage', 'label' => 'Manage', 'is_system' => false],
                'import' => ['name' => 'admin-expense.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'admin-expense.export', 'label' => 'Export', 'is_system' => false],
            ]],

            'activity-log' => ['label' => 'Activity Log', 'actions' => [
                'view' => ['name' => 'activity-log.view', 'label' => 'View', 'is_system' => false],
            ]],

            'login-history' => ['label' => 'Login History', 'actions' => [
                'view' => ['name' => 'login-history.view', 'label' => 'View', 'is_system' => false],
            ]],

            'notification' => ['label' => 'Notifications', 'actions' => [
                'view'            => ['name' => 'notification.view', 'label' => 'View', 'is_system' => false],
                'setting_manage'  => ['name' => 'notification-setting.manage', 'label' => 'Manage Notification Settings', 'is_system' => false],
            ]],

            'reports' => ['label' => 'Reports', 'actions' => [
                'supplier_ledger'          => ['name' => 'reports.supplier-ledger.view', 'label' => 'Supplier Ledger', 'is_system' => false],
                'supplier_ledger_print' => ['name' => 'reports.supplier-ledger.print', 'label' => 'Supplier Ledger - Print', 'is_system' => false],
                'supplier_ledger_pdf' => ['name' => 'reports.supplier-ledger.pdf', 'label' => 'Supplier Ledger - PDF', 'is_system' => false],
                'supplier_ledger_export' => ['name' => 'reports.supplier-ledger.export', 'label' => 'Supplier Ledger - Export (Excel)', 'is_system' => false],
                'supplier_ledger_export_csv' => ['name' => 'reports.supplier-ledger.export-csv', 'label' => 'Supplier Ledger - Export (CSV)', 'is_system' => false],
                'supplier_aging'           => ['name' => 'reports.supplier-aging.view', 'label' => 'Supplier Aging', 'is_system' => false],
                'supplier_aging_print' => ['name' => 'reports.supplier-aging.print', 'label' => 'Supplier Aging - Print', 'is_system' => false],
                'supplier_aging_pdf' => ['name' => 'reports.supplier-aging.pdf', 'label' => 'Supplier Aging - PDF', 'is_system' => false],
                'supplier_aging_export' => ['name' => 'reports.supplier-aging.export', 'label' => 'Supplier Aging - Export (Excel)', 'is_system' => false],
                'supplier_aging_export_csv' => ['name' => 'reports.supplier-aging.export-csv', 'label' => 'Supplier Aging - Export (CSV)', 'is_system' => false],
                'accounts_payable'         => ['name' => 'reports.accounts-payable.view', 'label' => 'Accounts Payable', 'is_system' => false],
                'accounts_payable_print' => ['name' => 'reports.accounts-payable.print', 'label' => 'Accounts Payable - Print', 'is_system' => false],
                'accounts_payable_pdf' => ['name' => 'reports.accounts-payable.pdf', 'label' => 'Accounts Payable - PDF', 'is_system' => false],
                'accounts_payable_export' => ['name' => 'reports.accounts-payable.export', 'label' => 'Accounts Payable - Export (Excel)', 'is_system' => false],
                'accounts_payable_export_csv' => ['name' => 'reports.accounts-payable.export-csv', 'label' => 'Accounts Payable - Export (CSV)', 'is_system' => false],
                'supplier_payment_history' => ['name' => 'reports.supplier-payment-history.view', 'label' => 'Supplier Payment History', 'is_system' => false],
                'supplier_payment_history_print' => ['name' => 'reports.supplier-payment-history.print', 'label' => 'Supplier Payment History - Print', 'is_system' => false],
                'supplier_payment_history_pdf' => ['name' => 'reports.supplier-payment-history.pdf', 'label' => 'Supplier Payment History - PDF', 'is_system' => false],
                'supplier_payment_history_export' => ['name' => 'reports.supplier-payment-history.export', 'label' => 'Supplier Payment History - Export (Excel)', 'is_system' => false],
                'supplier_payment_history_export_csv' => ['name' => 'reports.supplier-payment-history.export-csv', 'label' => 'Supplier Payment History - Export (CSV)', 'is_system' => false],
                'customer_ledger'          => ['name' => 'reports.customer-ledger.view', 'label' => 'Customer Ledger', 'is_system' => false],
                'customer_ledger_print' => ['name' => 'reports.customer-ledger.print', 'label' => 'Customer Ledger - Print', 'is_system' => false],
                'customer_ledger_pdf' => ['name' => 'reports.customer-ledger.pdf', 'label' => 'Customer Ledger - PDF', 'is_system' => false],
                'customer_ledger_export' => ['name' => 'reports.customer-ledger.export', 'label' => 'Customer Ledger - Export (Excel)', 'is_system' => false],
                'customer_ledger_export_csv' => ['name' => 'reports.customer-ledger.export-csv', 'label' => 'Customer Ledger - Export (CSV)', 'is_system' => false],
                'customer_aging'           => ['name' => 'reports.customer-aging.view', 'label' => 'Customer Aging', 'is_system' => false],
                'customer_aging_print' => ['name' => 'reports.customer-aging.print', 'label' => 'Customer Aging - Print', 'is_system' => false],
                'customer_aging_pdf' => ['name' => 'reports.customer-aging.pdf', 'label' => 'Customer Aging - PDF', 'is_system' => false],
                'customer_aging_export' => ['name' => 'reports.customer-aging.export', 'label' => 'Customer Aging - Export (Excel)', 'is_system' => false],
                'customer_aging_export_csv' => ['name' => 'reports.customer-aging.export-csv', 'label' => 'Customer Aging - Export (CSV)', 'is_system' => false],
                'accounts_receivable'      => ['name' => 'reports.accounts-receivable.view', 'label' => 'Accounts Receivable', 'is_system' => false],
                'accounts_receivable_print' => ['name' => 'reports.accounts-receivable.print', 'label' => 'Accounts Receivable - Print', 'is_system' => false],
                'accounts_receivable_pdf' => ['name' => 'reports.accounts-receivable.pdf', 'label' => 'Accounts Receivable - PDF', 'is_system' => false],
                'accounts_receivable_export' => ['name' => 'reports.accounts-receivable.export', 'label' => 'Accounts Receivable - Export (Excel)', 'is_system' => false],
                'accounts_receivable_export_csv' => ['name' => 'reports.accounts-receivable.export-csv', 'label' => 'Accounts Receivable - Export (CSV)', 'is_system' => false],
                'customer_payment_history' => ['name' => 'reports.customer-payment-history.view', 'label' => 'Customer Payment History', 'is_system' => false],
                'customer_payment_history_print' => ['name' => 'reports.customer-payment-history.print', 'label' => 'Customer Payment History - Print', 'is_system' => false],
                'customer_payment_history_pdf' => ['name' => 'reports.customer-payment-history.pdf', 'label' => 'Customer Payment History - PDF', 'is_system' => false],
                'customer_payment_history_export' => ['name' => 'reports.customer-payment-history.export', 'label' => 'Customer Payment History - Export (Excel)', 'is_system' => false],
                'customer_payment_history_export_csv' => ['name' => 'reports.customer-payment-history.export-csv', 'label' => 'Customer Payment History - Export (CSV)', 'is_system' => false],
                'purchase_return_summary'  => ['name' => 'reports.purchase-return-summary.view', 'label' => 'Purchase Return Summary', 'is_system' => false],
                'purchase_return_summary_print' => ['name' => 'reports.purchase-return-summary.print', 'label' => 'Purchase Return Summary - Print', 'is_system' => false],
                'purchase_return_summary_pdf' => ['name' => 'reports.purchase-return-summary.pdf', 'label' => 'Purchase Return Summary - PDF', 'is_system' => false],
                'purchase_return_summary_export' => ['name' => 'reports.purchase-return-summary.export', 'label' => 'Purchase Return Summary - Export (Excel)', 'is_system' => false],
                'purchase_return_summary_export_csv' => ['name' => 'reports.purchase-return-summary.export-csv', 'label' => 'Purchase Return Summary - Export (CSV)', 'is_system' => false],
                'purchase_return_detail'   => ['name' => 'reports.purchase-return-detail.view', 'label' => 'Purchase Return Detail', 'is_system' => false],
                'purchase_return_detail_print' => ['name' => 'reports.purchase-return-detail.print', 'label' => 'Purchase Return Detail - Print', 'is_system' => false],
                'purchase_return_detail_pdf' => ['name' => 'reports.purchase-return-detail.pdf', 'label' => 'Purchase Return Detail - PDF', 'is_system' => false],
                'purchase_return_detail_export' => ['name' => 'reports.purchase-return-detail.export', 'label' => 'Purchase Return Detail - Export (Excel)', 'is_system' => false],
                'purchase_return_detail_export_csv' => ['name' => 'reports.purchase-return-detail.export-csv', 'label' => 'Purchase Return Detail - Export (CSV)', 'is_system' => false],
                'stock_ledger'             => ['name' => 'reports.stock-ledger.view', 'label' => 'Stock Ledger & Movement', 'is_system' => false],
                'stock_ledger_print' => ['name' => 'reports.stock-ledger.print', 'label' => 'Stock Ledger & Movement - Print', 'is_system' => false],
                'stock_ledger_pdf' => ['name' => 'reports.stock-ledger.pdf', 'label' => 'Stock Ledger & Movement - PDF', 'is_system' => false],
                'stock_ledger_export' => ['name' => 'reports.stock-ledger.export', 'label' => 'Stock Ledger & Movement - Export (Excel)', 'is_system' => false],
                'stock_ledger_export_csv' => ['name' => 'reports.stock-ledger.export-csv', 'label' => 'Stock Ledger & Movement - Export (CSV)', 'is_system' => false],
                'general_ledger'           => ['name' => 'reports.general-ledger.view', 'label' => 'General Ledger', 'is_system' => false],
                'general_ledger_print' => ['name' => 'reports.general-ledger.print', 'label' => 'General Ledger - Print', 'is_system' => false],
                'general_ledger_pdf' => ['name' => 'reports.general-ledger.pdf', 'label' => 'General Ledger - PDF', 'is_system' => false],
                'general_ledger_export' => ['name' => 'reports.general-ledger.export', 'label' => 'General Ledger - Export (Excel)', 'is_system' => false],
                'general_ledger_export_csv' => ['name' => 'reports.general-ledger.export-csv', 'label' => 'General Ledger - Export (CSV)', 'is_system' => false],
                'trial_balance'            => ['name' => 'reports.trial-balance.view', 'label' => 'Trial Balance', 'is_system' => false],
                'trial_balance_print' => ['name' => 'reports.trial-balance.print', 'label' => 'Trial Balance - Print', 'is_system' => false],
                'trial_balance_pdf' => ['name' => 'reports.trial-balance.pdf', 'label' => 'Trial Balance - PDF', 'is_system' => false],
                'trial_balance_export' => ['name' => 'reports.trial-balance.export', 'label' => 'Trial Balance - Export (Excel)', 'is_system' => false],
                'trial_balance_export_csv' => ['name' => 'reports.trial-balance.export-csv', 'label' => 'Trial Balance - Export (CSV)', 'is_system' => false],
                'journal_register'         => ['name' => 'reports.journal-register.view', 'label' => 'Journal Register', 'is_system' => false],
                'journal_register_print' => ['name' => 'reports.journal-register.print', 'label' => 'Journal Register - Print', 'is_system' => false],
                'journal_register_pdf' => ['name' => 'reports.journal-register.pdf', 'label' => 'Journal Register - PDF', 'is_system' => false],
                'journal_register_export' => ['name' => 'reports.journal-register.export', 'label' => 'Journal Register - Export (Excel)', 'is_system' => false],
                'journal_register_export_csv' => ['name' => 'reports.journal-register.export-csv', 'label' => 'Journal Register - Export (CSV)', 'is_system' => false],
                'account_ledger'           => ['name' => 'reports.account-ledger.view', 'label' => 'Account Ledger', 'is_system' => false],
                'account_ledger_print' => ['name' => 'reports.account-ledger.print', 'label' => 'Account Ledger - Print', 'is_system' => false],
                'account_ledger_pdf' => ['name' => 'reports.account-ledger.pdf', 'label' => 'Account Ledger - PDF', 'is_system' => false],
                'account_ledger_export' => ['name' => 'reports.account-ledger.export', 'label' => 'Account Ledger - Export (Excel)', 'is_system' => false],
                'account_ledger_export_csv' => ['name' => 'reports.account-ledger.export-csv', 'label' => 'Account Ledger - Export (CSV)', 'is_system' => false],
                'account_balance'          => ['name' => 'reports.account-balance.view', 'label' => 'Account Balance', 'is_system' => false],
                'account_balance_print' => ['name' => 'reports.account-balance.print', 'label' => 'Account Balance - Print', 'is_system' => false],
                'account_balance_pdf' => ['name' => 'reports.account-balance.pdf', 'label' => 'Account Balance - PDF', 'is_system' => false],
                'account_balance_export' => ['name' => 'reports.account-balance.export', 'label' => 'Account Balance - Export (Excel)', 'is_system' => false],
                'account_balance_export_csv' => ['name' => 'reports.account-balance.export-csv', 'label' => 'Account Balance - Export (CSV)', 'is_system' => false],
                'day_book'                 => ['name' => 'reports.day-book.view', 'label' => 'Day Book', 'is_system' => false],
                'day_book_print' => ['name' => 'reports.day-book.print', 'label' => 'Day Book - Print', 'is_system' => false],
                'day_book_pdf' => ['name' => 'reports.day-book.pdf', 'label' => 'Day Book - PDF', 'is_system' => false],
                'day_book_export' => ['name' => 'reports.day-book.export', 'label' => 'Day Book - Export (Excel)', 'is_system' => false],
                'day_book_export_csv' => ['name' => 'reports.day-book.export-csv', 'label' => 'Day Book - Export (CSV)', 'is_system' => false],
                'cash_bank_ledger'         => ['name' => 'reports.cash-bank-ledger.view', 'label' => 'Cash & Bank Ledger', 'is_system' => false],
                'cash_bank_ledger_print' => ['name' => 'reports.cash-bank-ledger.print', 'label' => 'Cash & Bank Ledger - Print', 'is_system' => false],
                'cash_bank_ledger_pdf' => ['name' => 'reports.cash-bank-ledger.pdf', 'label' => 'Cash & Bank Ledger - PDF', 'is_system' => false],
                'cash_bank_ledger_export' => ['name' => 'reports.cash-bank-ledger.export', 'label' => 'Cash & Bank Ledger - Export (Excel)', 'is_system' => false],
                'cash_bank_ledger_export_csv' => ['name' => 'reports.cash-bank-ledger.export-csv', 'label' => 'Cash & Bank Ledger - Export (CSV)', 'is_system' => false],
                'income_report'            => ['name' => 'reports.income-report.view', 'label' => 'Income Report', 'is_system' => false],
                'income_report_print' => ['name' => 'reports.income-report.print', 'label' => 'Income Report - Print', 'is_system' => false],
                'income_report_pdf' => ['name' => 'reports.income-report.pdf', 'label' => 'Income Report - PDF', 'is_system' => false],
                'income_report_export' => ['name' => 'reports.income-report.export', 'label' => 'Income Report - Export (Excel)', 'is_system' => false],
                'income_report_export_csv' => ['name' => 'reports.income-report.export-csv', 'label' => 'Income Report - Export (CSV)', 'is_system' => false],
                'expense_report'           => ['name' => 'reports.expense-report.view', 'label' => 'Expense Report (By Account)', 'is_system' => false],
                'expense_report_print' => ['name' => 'reports.expense-report.print', 'label' => 'Expense Report (By Account) - Print', 'is_system' => false],
                'expense_report_pdf' => ['name' => 'reports.expense-report.pdf', 'label' => 'Expense Report (By Account) - PDF', 'is_system' => false],
                'expense_report_export' => ['name' => 'reports.expense-report.export', 'label' => 'Expense Report (By Account) - Export (Excel)', 'is_system' => false],
                'expense_report_export_csv' => ['name' => 'reports.expense-report.export-csv', 'label' => 'Expense Report (By Account) - Export (CSV)', 'is_system' => false],
                'expense_detail_report'    => ['name' => 'reports.expense-detail-report.view', 'label' => 'Expense Detail Report', 'is_system' => false],
                'expense_detail_report_print' => ['name' => 'reports.expense-detail-report.print', 'label' => 'Expense Detail Report - Print', 'is_system' => false],
                'expense_detail_report_pdf' => ['name' => 'reports.expense-detail-report.pdf', 'label' => 'Expense Detail Report - PDF', 'is_system' => false],
                'expense_detail_report_export' => ['name' => 'reports.expense-detail-report.export', 'label' => 'Expense Detail Report - Export (Excel)', 'is_system' => false],
                'expense_detail_report_export_csv' => ['name' => 'reports.expense-detail-report.export-csv', 'label' => 'Expense Detail Report - Export (CSV)', 'is_system' => false],
                'tax_report'               => ['name' => 'reports.tax-report.view', 'label' => 'Tax Report', 'is_system' => false],
                'tax_report_print' => ['name' => 'reports.tax-report.print', 'label' => 'Tax Report - Print', 'is_system' => false],
                'tax_report_pdf' => ['name' => 'reports.tax-report.pdf', 'label' => 'Tax Report - PDF', 'is_system' => false],
                'tax_report_export' => ['name' => 'reports.tax-report.export', 'label' => 'Tax Report - Export (Excel)', 'is_system' => false],
                'tax_report_export_csv' => ['name' => 'reports.tax-report.export-csv', 'label' => 'Tax Report - Export (CSV)', 'is_system' => false],
                'equity_report'            => ['name' => 'reports.equity-report.view', 'label' => 'Equity Report', 'is_system' => false],
                'equity_report_print' => ['name' => 'reports.equity-report.print', 'label' => 'Equity Report - Print', 'is_system' => false],
                'equity_report_pdf' => ['name' => 'reports.equity-report.pdf', 'label' => 'Equity Report - PDF', 'is_system' => false],
                'equity_report_export' => ['name' => 'reports.equity-report.export', 'label' => 'Equity Report - Export (Excel)', 'is_system' => false],
                'equity_report_export_csv' => ['name' => 'reports.equity-report.export-csv', 'label' => 'Equity Report - Export (CSV)', 'is_system' => false],
                'profit_loss'              => ['name' => 'reports.profit-loss.view', 'label' => 'Profit & Loss', 'is_system' => false],
                'profit_loss_print' => ['name' => 'reports.profit-loss.print', 'label' => 'Profit & Loss - Print', 'is_system' => false],
                'profit_loss_pdf' => ['name' => 'reports.profit-loss.pdf', 'label' => 'Profit & Loss - PDF', 'is_system' => false],
                'profit_loss_export' => ['name' => 'reports.profit-loss.export', 'label' => 'Profit & Loss - Export (Excel)', 'is_system' => false],
                'profit_loss_export_csv' => ['name' => 'reports.profit-loss.export-csv', 'label' => 'Profit & Loss - Export (CSV)', 'is_system' => false],
                'balance_sheet'            => ['name' => 'reports.balance-sheet.view', 'label' => 'Balance Sheet', 'is_system' => false],
                'balance_sheet_print' => ['name' => 'reports.balance-sheet.print', 'label' => 'Balance Sheet - Print', 'is_system' => false],
                'balance_sheet_pdf' => ['name' => 'reports.balance-sheet.pdf', 'label' => 'Balance Sheet - PDF', 'is_system' => false],
                'balance_sheet_export' => ['name' => 'reports.balance-sheet.export', 'label' => 'Balance Sheet - Export (Excel)', 'is_system' => false],
                'balance_sheet_export_csv' => ['name' => 'reports.balance-sheet.export-csv', 'label' => 'Balance Sheet - Export (CSV)', 'is_system' => false],
                // Visible in both Simple and Advanced Accounting Mode - see
                // BudgetVarianceReportController.
                'budget_vs_actual' => ['name' => 'reports.budget-vs-actual.view', 'label' => 'Budget vs Actual', 'is_system' => false],
            ]],
        ];
    }

    /**
     * Flat map of permission name => ['label', 'is_system']. Used by the seeder.
     */
    public static function flat(): array
    {
        $out = [];
        foreach (self::modules() as $module) {
            foreach ($module['actions'] as $action) {
                $out[$action['name']] = [
                    'label' => $action['label'],
                    'is_system' => $action['is_system'],
                ];
            }
        }
        return $out;
    }

    /**
     * Modules grouped for the Role UI. When $businessAdminOnly is true, every
     * is_system permission (and any module left with no remaining actions)
     * is filtered out.
     *
     * When $enabledModuleKeys is given (package-aware filtering - see
     * SubscriptionModuleRegistry::enabledPermissionModuleKeysFor()), any
     * module key not in that list is also stripped, so a Business Admin
     * only ever sees permissions for modules their subscription package
     * actually includes.
     */
    public static function grouped(bool $businessAdminOnly = false, ?array $enabledModuleKeys = null): array
    {
        $modules = self::modules();

        if ($businessAdminOnly) {
            foreach ($modules as $key => $module) {
                $modules[$key]['actions'] = array_filter(
                    $module['actions'],
                    fn ($action) => !$action['is_system']
                );

                if (empty($modules[$key]['actions'])) {
                    unset($modules[$key]);
                }
            }
        }

        if ($enabledModuleKeys !== null) {
            $modules = array_intersect_key($modules, array_flip($enabledModuleKeys));
        }

        return $modules;
    }

    /**
     * All permission names, e.g. for Super Admin's full syncPermissions().
     */
    public static function allNames(): array
    {
        return array_keys(self::flat());
    }

    /**
     * All non-system ("is_system=false") permission names, e.g. for Business
     * Admin's syncPermissions().
     */
    public static function businessNames(): array
    {
        return array_keys(array_filter(self::flat(), fn ($meta) => !$meta['is_system']));
    }

    /**
     * All permission names belonging to the given module keys.
     */
    public static function namesForModules(array $moduleKeys): array
    {
        $modules = self::modules();
        $names = [];
        foreach ($moduleKeys as $key) {
            if (!isset($modules[$key])) {
                continue;
            }
            foreach ($modules[$key]['actions'] as $action) {
                $names[] = $action['name'];
            }
        }
        return $names;
    }

    /**
     * All permission names belonging to the given module keys, skipping any
     * action whose action-key is in $excludeActionKeys (e.g. ['delete']).
     */
    public static function namesForModulesExcludingActions(array $moduleKeys, array $excludeActionKeys): array
    {
        $modules = self::modules();
        $names = [];
        foreach ($moduleKeys as $key) {
            if (!isset($modules[$key])) {
                continue;
            }
            foreach ($modules[$key]['actions'] as $actionKey => $action) {
                if (in_array($actionKey, $excludeActionKeys, true)) {
                    continue;
                }
                $names[] = $action['name'];
            }
        }
        return $names;
    }

    /**
     * Business/branch-scoped, non-platform module keys (excludes role,
     * setting, permission, package, business, subscription, my-subscription).
     * Used to build broad "manager" defaults without hand-listing every key.
     */
    public static function operationalModuleKeys(): array
    {
        return [
            'dashboard', 'warehouse', 'brand', 'category', 'sub-category', 'unit', 'product',
            'barcode', 'unit-conversion', 'batch', 'stock', 'stock-transaction',
            'account-type', 'account-sub-type', 'account', 'journal', 'journal-entry', 'recurring-transaction',
            'supplier', 'customer', 'order-type', 'payment-method', 'order-source', 'sale-type', 'discount', 'voucher',
            'purchase-request', 'purchase-request-quotation', 'purchase', 'good-receipt-note',
            'purchase-return', 'opening-stock', 'stock-taking', 'transfer-note', 'supplier-payment', 'customer-payment',
            'pos', 'order', 'order-return', 'expense', 'expense-category', 'admin-expense',
            'activity-log', 'login-history', 'notification', 'reports', 'branch', 'user',
            'department', 'designation', 'shift', 'employee', 'attendance', 'leave-type',
            'leave-request', 'salary-component', 'salary-structure', 'payroll', 'payslip',
            'employee-advance', 'employee-deduction', 'employee-ledger', 'employee-exit',
            'employee-clearance', 'asset', 'asset-allocation',
            'hrm-reports', 'payroll-reports',
        ];
    }
}
