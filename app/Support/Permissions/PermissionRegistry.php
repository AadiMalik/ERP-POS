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

            'supplier' => ['label' => 'Suppliers', 'actions' => [
                'view'   => ['name' => 'supplier.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'supplier.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'supplier.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'supplier.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'supplier.status', 'label' => 'Change Status', 'is_system' => false],
                'import' => ['name' => 'supplier.import', 'label' => 'Import', 'is_system' => false],
                'export' => ['name' => 'supplier.export', 'label' => 'Export', 'is_system' => false],
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
                'employee_directory' => ['name' => 'reports.employee-directory-report.view', 'label' => 'Employee Directory Report', 'is_system' => false],
                'employee_joining' => ['name' => 'reports.employee-joining-report.view', 'label' => 'Employee Joining Report', 'is_system' => false],
                'employee_exit' => ['name' => 'reports.employee-exit-report.view', 'label' => 'Employee Exit Report', 'is_system' => false],
                'department_wise_employee' => ['name' => 'reports.department-wise-employee-report.view', 'label' => 'Department-wise Employee Report', 'is_system' => false],
                'designation_wise_employee' => ['name' => 'reports.designation-wise-employee-report.view', 'label' => 'Designation-wise Employee Report', 'is_system' => false],
                'branch_wise_employee' => ['name' => 'reports.branch-wise-employee-report.view', 'label' => 'Branch-wise Employee Report', 'is_system' => false],
                'employee_status' => ['name' => 'reports.employee-status-report.view', 'label' => 'Employee Status Report', 'is_system' => false],
                'attendance_summary' => ['name' => 'reports.attendance-summary-report.view', 'label' => 'Attendance Summary Report', 'is_system' => false],
                'daily_attendance' => ['name' => 'reports.daily-attendance-report.view', 'label' => 'Daily Attendance Report', 'is_system' => false],
                'monthly_attendance' => ['name' => 'reports.monthly-attendance-report.view', 'label' => 'Monthly Attendance Report', 'is_system' => false],
                'attendance_register' => ['name' => 'reports.attendance-register.view', 'label' => 'Attendance Register', 'is_system' => false],
                'late_attendance' => ['name' => 'reports.late-attendance-report.view', 'label' => 'Late Attendance Report', 'is_system' => false],
                'early_checkout' => ['name' => 'reports.early-checkout-report.view', 'label' => 'Early Checkout Report', 'is_system' => false],
                'absent_employees' => ['name' => 'reports.absent-employees-report.view', 'label' => 'Absent Employees Report', 'is_system' => false],
                'missing_checkin_checkout' => ['name' => 'reports.missing-checkin-checkout-report.view', 'label' => 'Missing Check-In/Check-Out Report', 'is_system' => false],
                'overtime' => ['name' => 'reports.overtime-report.view', 'label' => 'Overtime Report', 'is_system' => false],
                'shift_wise_attendance' => ['name' => 'reports.shift-wise-attendance-report.view', 'label' => 'Shift-wise Attendance Report', 'is_system' => false],
                'shift_assignment' => ['name' => 'reports.shift-assignment-report.view', 'label' => 'Shift Assignment Report', 'is_system' => false],
                'leave_summary' => ['name' => 'reports.leave-summary-report.view', 'label' => 'Leave Summary Report', 'is_system' => false],
                'employee_leave_history' => ['name' => 'reports.employee-leave-history-report.view', 'label' => 'Employee Leave History Report', 'is_system' => false],
                'leave_type_wise' => ['name' => 'reports.leave-type-wise-report.view', 'label' => 'Leave Type-wise Report', 'is_system' => false],
                'department_wise_leave' => ['name' => 'reports.department-wise-leave-report.view', 'label' => 'Department-wise Leave Report', 'is_system' => false],
                'pending_leave_approval' => ['name' => 'reports.pending-leave-approval-report.view', 'label' => 'Pending Leave Approval Report', 'is_system' => false],
                'leave_approval_status' => ['name' => 'reports.leave-approval-status-report.view', 'label' => 'Leave Approval Status Report', 'is_system' => false],
                'leave_balance' => ['name' => 'reports.leave-balance-report.view', 'label' => 'Leave Balance Report', 'is_system' => false],
                'salary_structure' => ['name' => 'reports.salary-structure-report.view', 'label' => 'Salary Structure Report', 'is_system' => false],
                'salary_component' => ['name' => 'reports.salary-component-report.view', 'label' => 'Salary Component Report', 'is_system' => false],
                'deduction' => ['name' => 'reports.deduction-report.view', 'label' => 'Deduction Report', 'is_system' => false],
                'employee_advance' => ['name' => 'reports.employee-advance-report.view', 'label' => 'Employee Advance Report', 'is_system' => false],
                'advance_recovery' => ['name' => 'reports.advance-recovery-report.view', 'label' => 'Advance Recovery Report', 'is_system' => false],
                'employee_ledger' => ['name' => 'reports.employee-ledger-report.view', 'label' => 'Employee Ledger Report', 'is_system' => false],
                'resignation' => ['name' => 'reports.resignation-report.view', 'label' => 'Resignation Report', 'is_system' => false],
                'termination' => ['name' => 'reports.termination-report.view', 'label' => 'Termination Report', 'is_system' => false],
                'employee_clearance' => ['name' => 'reports.employee-clearance-report.view', 'label' => 'Employee Clearance Report', 'is_system' => false],
                'asset_allocation' => ['name' => 'reports.asset-allocation-report.view', 'label' => 'Asset Allocation Report', 'is_system' => false],
                'employee_asset_return' => ['name' => 'reports.employee-asset-return-report.view', 'label' => 'Employee Asset Return Report', 'is_system' => false],
                'employee_document' => ['name' => 'reports.employee-document-report.view', 'label' => 'Employee Document Report', 'is_system' => false],
                'employee_lifecycle' => ['name' => 'reports.employee-lifecycle-report.view', 'label' => 'Employee Lifecycle Report', 'is_system' => false],
                'hr_dashboard' => ['name' => 'reports.hr-dashboard-report.view', 'label' => 'HR Dashboard Report', 'is_system' => false],
            ]],

            'payroll-reports' => ['label' => 'Payroll Reports', 'actions' => [
                'payroll_summary' => ['name' => 'reports.payroll-summary-report.view', 'label' => 'Payroll Summary Report', 'is_system' => false],
                'employee_wise_payroll' => ['name' => 'reports.employee-wise-payroll-report.view', 'label' => 'Employee-wise Payroll Report', 'is_system' => false],
                'department_wise_payroll' => ['name' => 'reports.department-wise-payroll-report.view', 'label' => 'Department-wise Payroll Report', 'is_system' => false],
                'branch_wise_payroll' => ['name' => 'reports.branch-wise-payroll-report.view', 'label' => 'Branch-wise Payroll Report', 'is_system' => false],
                'monthly_payroll_register' => ['name' => 'reports.monthly-payroll-register.view', 'label' => 'Monthly Payroll Register', 'is_system' => false],
                'payroll_cost' => ['name' => 'reports.payroll-cost-report.view', 'label' => 'Payroll Cost Report', 'is_system' => false],
                'pending_payroll' => ['name' => 'reports.pending-payroll-report.view', 'label' => 'Pending Payroll Report', 'is_system' => false],
                'salary_slip' => ['name' => 'reports.salary-slip-report.view', 'label' => 'Salary Slip Report', 'is_system' => false],
                'payroll_disbursement' => ['name' => 'reports.payroll-disbursement-report.view', 'label' => 'Payroll Payment/Disbursement Report', 'is_system' => false],
                'attendance_payroll_comparison' => ['name' => 'reports.attendance-payroll-comparison-report.view', 'label' => 'Employee Attendance & Payroll Comparison Report', 'is_system' => false],
                'leave_payroll_impact' => ['name' => 'reports.leave-payroll-impact-report.view', 'label' => 'Leave & Payroll Impact Report', 'is_system' => false],
                'employee_cost' => ['name' => 'reports.employee-cost-report.view', 'label' => 'Employee Cost Report', 'is_system' => false],
                'department_payroll_cost' => ['name' => 'reports.department-payroll-cost-report.view', 'label' => 'Department Payroll Cost Report', 'is_system' => false],
                'branch_payroll_cost' => ['name' => 'reports.branch-payroll-cost-report.view', 'label' => 'Branch Payroll Cost Report', 'is_system' => false],
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
                'hold'            => ['name' => 'order.hold', 'label' => 'Hold', 'is_system' => false],
                'cancel_void'     => ['name' => 'order.cancel_void', 'label' => 'Cancel / Void', 'is_system' => false],
                'refund_process'  => ['name' => 'order.refund.process', 'label' => 'Process Refund', 'is_system' => false],
                'payment_credit'  => ['name' => 'order.payment.credit', 'label' => 'Credit Payment', 'is_system' => false],
                'customer_change' => ['name' => 'order.customer.change', 'label' => 'Change Customer', 'is_system' => false],
                'reopen'          => ['name' => 'order.reopen', 'label' => 'Reopen', 'is_system' => false],
                'export'          => ['name' => 'order.export', 'label' => 'Export', 'is_system' => false],
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
                'supplier_aging'           => ['name' => 'reports.supplier-aging.view', 'label' => 'Supplier Aging', 'is_system' => false],
                'accounts_payable'         => ['name' => 'reports.accounts-payable.view', 'label' => 'Accounts Payable', 'is_system' => false],
                'supplier_payment_history' => ['name' => 'reports.supplier-payment-history.view', 'label' => 'Supplier Payment History', 'is_system' => false],
                'purchase_return_summary'  => ['name' => 'reports.purchase-return-summary.view', 'label' => 'Purchase Return Summary', 'is_system' => false],
                'purchase_return_detail'   => ['name' => 'reports.purchase-return-detail.view', 'label' => 'Purchase Return Detail', 'is_system' => false],
                'stock_ledger'             => ['name' => 'reports.stock-ledger.view', 'label' => 'Stock Ledger & Movement', 'is_system' => false],
                'general_ledger'           => ['name' => 'reports.general-ledger.view', 'label' => 'General Ledger', 'is_system' => false],
                'trial_balance'            => ['name' => 'reports.trial-balance.view', 'label' => 'Trial Balance', 'is_system' => false],
                'journal_register'         => ['name' => 'reports.journal-register.view', 'label' => 'Journal Register', 'is_system' => false],
                'account_ledger'           => ['name' => 'reports.account-ledger.view', 'label' => 'Account Ledger', 'is_system' => false],
                'account_balance'          => ['name' => 'reports.account-balance.view', 'label' => 'Account Balance', 'is_system' => false],
                'day_book'                 => ['name' => 'reports.day-book.view', 'label' => 'Day Book', 'is_system' => false],
                'cash_bank_ledger'         => ['name' => 'reports.cash-bank-ledger.view', 'label' => 'Cash & Bank Ledger', 'is_system' => false],
                'income_report'            => ['name' => 'reports.income-report.view', 'label' => 'Income Report', 'is_system' => false],
                'expense_report'           => ['name' => 'reports.expense-report.view', 'label' => 'Expense Report (By Account)', 'is_system' => false],
                'expense_detail_report'    => ['name' => 'reports.expense-detail-report.view', 'label' => 'Expense Detail Report', 'is_system' => false],
                'tax_report'               => ['name' => 'reports.tax-report.view', 'label' => 'Tax Report', 'is_system' => false],
                'equity_report'            => ['name' => 'reports.equity-report.view', 'label' => 'Equity Report', 'is_system' => false],
                'profit_loss'              => ['name' => 'reports.profit-loss.view', 'label' => 'Profit & Loss', 'is_system' => false],
                'balance_sheet'            => ['name' => 'reports.balance-sheet.view', 'label' => 'Balance Sheet', 'is_system' => false],
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
            'supplier', 'order-type', 'payment-method', 'order-source', 'discount', 'voucher',
            'purchase-request', 'purchase-request-quotation', 'purchase', 'good-receipt-note',
            'purchase-return', 'opening-stock', 'stock-taking', 'transfer-note', 'supplier-payment',
            'pos', 'order', 'expense', 'expense-category', 'admin-expense',
            'activity-log', 'login-history', 'notification', 'reports', 'branch', 'user',
            'department', 'designation', 'shift', 'employee', 'attendance', 'leave-type',
            'leave-request', 'salary-component', 'salary-structure', 'payroll', 'payslip',
            'employee-advance', 'employee-deduction', 'employee-ledger', 'employee-exit',
            'employee-clearance', 'asset', 'asset-allocation',
            'hrm-reports', 'payroll-reports',
        ];
    }
}
