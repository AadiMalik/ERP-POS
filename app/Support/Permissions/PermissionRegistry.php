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
                // Already fully enforced via the `superadmin` route-group middleware.
                // Registered here only so it appears in the Role UI/seeder for completeness.
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
            ]],

            'warehouse' => ['label' => 'Warehouse', 'actions' => [
                'view'   => ['name' => 'warehouse.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'warehouse.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'warehouse.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'warehouse.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'warehouse.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'brand' => ['label' => 'Brands', 'actions' => [
                'view'   => ['name' => 'brand.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'brand.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'brand.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'brand.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'brand.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'category' => ['label' => 'Categories', 'actions' => [
                'view'   => ['name' => 'category.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'category.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'category.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'category.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'category.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'sub-category' => ['label' => 'Sub Categories', 'actions' => [
                'view'   => ['name' => 'sub-category.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'sub-category.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'sub-category.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'sub-category.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'sub-category.status', 'label' => 'Change Status', 'is_system' => false],
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
            ]],

            'supplier' => ['label' => 'Suppliers', 'actions' => [
                'view'   => ['name' => 'supplier.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'supplier.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'supplier.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'supplier.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'supplier.status', 'label' => 'Change Status', 'is_system' => false],
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
            ]],

            'voucher' => ['label' => 'Vouchers', 'actions' => [
                'view'   => ['name' => 'voucher.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'voucher.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'voucher.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'voucher.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'voucher.status', 'label' => 'Change Status', 'is_system' => false],
            ]],

            'purchase-request' => ['label' => 'Purchase Requests', 'actions' => [
                'view'   => ['name' => 'purchase-request.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'purchase-request.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'purchase-request.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'purchase-request.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'purchase-request.status', 'label' => 'Change Status', 'is_system' => false],
                'print'  => ['name' => 'purchase-request.print', 'label' => 'Print', 'is_system' => false],
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
            ]],

            'supplier-payment' => ['label' => 'Supplier Payments', 'actions' => [
                'view'   => ['name' => 'supplier-payment.view', 'label' => 'View', 'is_system' => false],
                'create' => ['name' => 'supplier-payment.create', 'label' => 'Create', 'is_system' => false],
                'edit'   => ['name' => 'supplier-payment.edit', 'label' => 'Edit', 'is_system' => false],
                'delete' => ['name' => 'supplier-payment.delete', 'label' => 'Delete', 'is_system' => false],
                'status' => ['name' => 'supplier-payment.status', 'label' => 'Change Status', 'is_system' => false],
                'print'  => ['name' => 'supplier-payment.print', 'label' => 'Print', 'is_system' => false],
            ]],

            'setting' => ['label' => 'Settings', 'actions' => [
                'manage' => ['name' => 'setting.manage', 'label' => 'Manage', 'is_system' => false],
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
            ]],

            'expense' => ['label' => 'Expense (POS)', 'actions' => [
                'access'      => ['name' => 'expense.access', 'label' => 'Access', 'is_system' => false],
                'view'        => ['name' => 'expense.view', 'label' => 'View', 'is_system' => false],
                'create'      => ['name' => 'expense.create', 'label' => 'Create', 'is_system' => false],
                'edit'        => ['name' => 'expense.edit', 'label' => 'Edit', 'is_system' => false],
                'post'        => ['name' => 'expense.post', 'label' => 'Post', 'is_system' => false],
                'delete'      => ['name' => 'expense.delete', 'label' => 'Delete', 'is_system' => false],
                'report_view' => ['name' => 'expense.report.view', 'label' => 'View Report', 'is_system' => false],
            ]],

            'expense-category' => ['label' => 'Expense Category', 'actions' => [
                'manage' => ['name' => 'expense-category.manage', 'label' => 'Manage', 'is_system' => false],
            ]],

            'admin-expense' => ['label' => 'Admin Expenses', 'actions' => [
                'manage' => ['name' => 'admin-expense.manage', 'label' => 'Manage', 'is_system' => false],
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
     */
    public static function grouped(bool $businessAdminOnly = false): array
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
            'account-type', 'account-sub-type', 'account', 'journal', 'journal-entry',
            'supplier', 'order-type', 'payment-method', 'order-source', 'discount', 'voucher',
            'purchase-request', 'purchase-request-quotation', 'purchase', 'good-receipt-note',
            'purchase-return', 'opening-stock', 'stock-taking', 'transfer-note', 'supplier-payment',
            'pos', 'order', 'expense', 'expense-category', 'admin-expense',
            'activity-log', 'login-history', 'notification', 'reports', 'branch', 'user',
        ];
    }
}
