<?php

namespace App\Support\Permissions;

use App\Enums\RoleNames;

/**
 * Default permission set assigned to each role template when it is created
 * or reset. Consumed by:
 *  - database/seeders/PermissionSeeder.php (for the 2 global roles: Super
 *    Admin, Business Admin)
 *  - RoleService::resetBusinessRoles() (for the 13 business/branch-scoped
 *    role templates, which only exist per-tenant and can't be pre-seeded
 *    globally)
 *
 * These are starting points, not hard limits — a Super Admin/Business Admin
 * can adjust any individual role's permissions afterwards via the Role
 * Create/Edit screen.
 */
class RoleDefaultPermissions
{
    public static function defaultsForRole(string $roleName): array
    {
        switch ($roleName) {
            case RoleNames::SUPERADMIN:
                return PermissionRegistry::allNames();

            case RoleNames::BUSINESSADMIN:
                return PermissionRegistry::businessNames();

            case RoleNames::BRANCHADMIN:
                // Everything a Business Admin has, minus role/permission management
                // and global settings (branch-scoped operational admin).
                return array_values(array_diff(
                    PermissionRegistry::businessNames(),
                    PermissionRegistry::namesForModules(['role', 'setting'])
                ));

            case RoleNames::GENERALMANAGER:
                // Broad visibility + edit rights across the business, no deletes.
                return PermissionRegistry::namesForModulesExcludingActions(
                    PermissionRegistry::operationalModuleKeys(),
                    ['delete']
                );

            case RoleNames::OPERATIONMANAGER:
                return PermissionRegistry::namesForModulesExcludingActions(
                    ['warehouse', 'brand', 'category', 'sub-category', 'unit', 'product', 'barcode',
                     'unit-conversion', 'batch', 'stock', 'stock-transaction', 'opening-stock',
                     'stock-taking', 'transfer-note', 'supplier', 'customer', 'purchase-request',
                     'purchase-request-quotation', 'purchase', 'good-receipt-note', 'purchase-return',
                     'supplier-payment', 'customer-payment', 'service-purchase', 'service-purchase-return',
                     'service-sale', 'service-sale-return', 'order-type', 'payment-method', 'order-source', 'discount',
                     'voucher', 'order', 'order-return', 'pos', 'pos-register', 'expense', 'expense-category', 'admin-expense'],
                    ['delete']
                );

            case RoleNames::INVENTORYMANAGER:
                return array_merge(
                    PermissionRegistry::namesForModules([
                        'warehouse', 'brand', 'category', 'sub-category', 'unit', 'product', 'barcode',
                        'unit-conversion', 'batch', 'stock', 'stock-transaction', 'opening-stock',
                        'stock-taking', 'transfer-note', 'purchase-return',
                    ]),
                    [
                        'dashboard.view',
                        'reports.stock-ledger.view', 'reports.stock-ledger.print', 'reports.stock-ledger.pdf',
                        'reports.stock-ledger.export', 'reports.stock-ledger.export-csv',
                    ]
                );

            case RoleNames::FINANCEMANAGER:
                return array_merge(
                    PermissionRegistry::namesForModules([
                        'account-type', 'account-sub-type', 'account', 'journal', 'journal-entry', 'bank-reconciliation', 'recurring-transaction',
                        'fixed-asset-category', 'fixed-asset', 'fixed-asset-depreciation',
                        'expense', 'expense-category', 'admin-expense', 'supplier-payment', 'customer-payment',
                        'fiscal-year', 'accounting-period', 'period-closing-rule', 'budget',
                    ]),
                    array_merge(
                        ['dashboard.view'],
                        self::formatVariants([
                            'general-ledger', 'trial-balance', 'journal-register', 'account-ledger',
                            'account-balance', 'day-book', 'cash-bank-ledger', 'income-report',
                            'expense-report', 'expense-detail-report', 'tax-report', 'equity-report',
                            'profit-loss', 'balance-sheet', 'accounts-payable', 'accounts-receivable',
                            'service-transaction-summary', 'service-payment-report',
                            'fixed-asset-register', 'depreciation-report', 'asset-valuation-report', 'asset-disposal-report',
                        ]),
                        ['reports.budget-vs-actual.view']
                    )
                );

            case RoleNames::SALEMANAGER:
                return array_merge(
                    PermissionRegistry::namesForModules(['order-type', 'payment-method', 'order-source', 'sale-type', 'discount', 'voucher', 'order', 'order-return', 'pos', 'customer', 'customer-payment', 'service-sale', 'service-sale-return']),
                    array_merge(
                        ['dashboard.view'],
                        self::formatVariants([
                            'customer-ledger', 'customer-aging', 'customer-payment-history', 'sales-report', 'service-sale-report',
                            'order-detail', 'product-sales', 'variation-sales', 'customer-sales', 'branch-sales',
                            'order-source-sales', 'payment-method-sales', 'order-status-report', 'cancelled-orders',
                            'due-credit-sales', 'discount-report', 'order-tax-report', 'top-selling', 'offline-orders-report',
                        ])
                    )
                );

            case RoleNames::PURCHASEMANAGER:
                return array_merge(
                    PermissionRegistry::namesForModules([
                        'supplier', 'purchase-request', 'purchase-request-quotation', 'purchase',
                        'good-receipt-note', 'purchase-return', 'supplier-payment',
                        'service-purchase', 'service-purchase-return',
                    ]),
                    array_merge(
                        ['dashboard.view'],
                        self::formatVariants([
                            'supplier-ledger', 'supplier-aging', 'accounts-payable',
                            'supplier-payment-history', 'purchase-return-summary', 'purchase-return-detail',
                            'service-purchase-report',
                        ])
                    )
                );

            case RoleNames::MARKITINGMANAGER:
                return array_merge(
                    PermissionRegistry::namesForModulesExcludingActions(['discount', 'voucher', 'order-source'], ['delete']),
                    PermissionRegistry::namesForModules([
                        'firebase-setting',
                        'notification-template',
                        'broadcast-notification',
                    ]),
                    ['dashboard.view']
                );

            case RoleNames::ACCOUNTANT:
                return array_merge(
                    PermissionRegistry::namesForModulesExcludingActions(
                        [
                            'account-type', 'account-sub-type', 'account', 'journal', 'journal-entry', 'bank-reconciliation', 'recurring-transaction',
                            'fixed-asset-category', 'fixed-asset', 'fixed-asset-depreciation',
                            'expense', 'expense-category', 'admin-expense',
                            'fiscal-year', 'accounting-period', 'period-closing-rule', 'budget',
                        ],
                        ['delete']
                    ),
                    array_merge(
                        ['dashboard.view'],
                        self::formatVariants([
                            'general-ledger', 'trial-balance', 'journal-register',
                            'account-ledger', 'account-balance', 'day-book',
                            'service-transaction-summary', 'service-payment-report',
                            'fixed-asset-register', 'depreciation-report', 'asset-valuation-report', 'asset-disposal-report',
                        ]),
                        ['reports.budget-vs-actual.view']
                    )
                );

            case RoleNames::HRMANAGER:
                return array_merge(
                    PermissionRegistry::namesForModules([
                        'user',
                        'department', 'designation', 'shift', 'employee', 'attendance',
                        'leave-type', 'leave-request', 'salary-component', 'salary-structure',
                        'payroll', 'payslip', 'employee-advance', 'employee-deduction',
                        'employee-ledger', 'employee-exit', 'employee-clearance',
                        'asset', 'asset-allocation', 'hrm-reports', 'payroll-reports',
                    ]),
                    ['dashboard.view', 'branch.view']
                );

            case RoleNames::EMPLOYEE:
                return PermissionRegistry::namesForModules(['ess']);

            case RoleNames::REPORTINGANALYST:
                // hrm-reports (organizational/attendance/leave) is included here, but
                // payroll-reports (salary/payroll-run/cost data) deliberately is not -
                // see PermissionRegistry's "HRM & Payroll Reports" comment.
                return array_merge(
                    PermissionRegistry::namesForModules(['reports', 'hrm-reports']),
                    ['dashboard.view']
                );

            case RoleNames::STAFF:
                return ['dashboard.view', 'expense.access', 'expense.view'];

            case RoleNames::POSMANAGER:
                return [
                    'pos.access',
                    'pos.register.close',
                    'pos.register.report.view',
                    'pos.register.cash-movement.manage',
                    'pos-register.view',
                    'order.create',
                    'order.edit',
                    'order.complete',
                    'order.discount.apply',
                    'order.coupon.apply',
                    'order.price.change',
                    'order.price.override-minimum',
                    'order.hold',
                    'order.cancel',
                    'order.void',
                    'order.delete',
                    'order.refund.process',
                    'order.payment.credit',
                    'order.customer.change',
                    'order.reopen',
                    'order-return.view',
                    'order-return.create',
                    'order-return.edit',
                    'order-return.approve',
                    'order-return.print',
                    'expense.access',
                    'expense.view',
                ];

            case RoleNames::ORDERTAKER:
                return [
                    'pos.access',
                    'order.create',
                    'order.complete',
                    'order.hold',
                    'expense.access',
                ];

            case RoleNames::USER:
            default:
                return [];
        }
    }

    /**
     * Expands a list of `reports.<slug>` names into their full view/print/
     * pdf/export/export-csv permission bundle - used by role templates that
     * hand-pick individual reports (rather than a whole hrm-reports/
     * payroll-reports/reports module via PermissionRegistry::namesForModules())
     * so the role gets full access to each named report, not just `.view`.
     */
    private static function formatVariants(array $reportSlugs): array
    {
        $names = [];
        foreach ($reportSlugs as $slug) {
            foreach (['view', 'print', 'pdf', 'export', 'export-csv'] as $format) {
                $names[] = "reports.{$slug}.{$format}";
            }
        }
        return $names;
    }
}
