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
                     'stock-taking', 'transfer-note', 'supplier', 'purchase-request',
                     'purchase-request-quotation', 'purchase', 'good-receipt-note', 'purchase-return',
                     'supplier-payment', 'order-type', 'payment-method', 'order-source', 'discount',
                     'voucher', 'order', 'pos', 'expense', 'expense-category', 'admin-expense'],
                    ['delete']
                );

            case RoleNames::INVENTORYMANAGER:
                return array_merge(
                    PermissionRegistry::namesForModules([
                        'warehouse', 'brand', 'category', 'sub-category', 'unit', 'product', 'barcode',
                        'unit-conversion', 'batch', 'stock', 'stock-transaction', 'opening-stock',
                        'stock-taking', 'transfer-note',
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
                        'account-type', 'account-sub-type', 'account', 'journal', 'journal-entry', 'recurring-transaction',
                        'expense', 'expense-category', 'admin-expense', 'supplier-payment',
                    ]),
                    array_merge(
                        ['dashboard.view'],
                        self::formatVariants([
                            'general-ledger', 'trial-balance', 'journal-register', 'account-ledger',
                            'account-balance', 'day-book', 'cash-bank-ledger', 'income-report',
                            'expense-report', 'expense-detail-report', 'tax-report', 'equity-report',
                            'profit-loss', 'balance-sheet', 'accounts-payable',
                        ])
                    )
                );

            case RoleNames::SALEMANAGER:
                return array_merge(
                    PermissionRegistry::namesForModules(['order-type', 'payment-method', 'order-source', 'discount', 'voucher', 'order', 'pos']),
                    ['dashboard.view']
                );

            case RoleNames::PURCHASEMANAGER:
                return array_merge(
                    PermissionRegistry::namesForModules([
                        'supplier', 'purchase-request', 'purchase-request-quotation', 'purchase',
                        'good-receipt-note', 'purchase-return', 'supplier-payment',
                    ]),
                    array_merge(
                        ['dashboard.view'],
                        self::formatVariants([
                            'supplier-ledger', 'supplier-aging', 'accounts-payable',
                            'supplier-payment-history', 'purchase-return-summary', 'purchase-return-detail',
                        ])
                    )
                );

            case RoleNames::MARKITINGMANAGER:
                return array_merge(
                    PermissionRegistry::namesForModulesExcludingActions(['discount', 'voucher', 'order-source'], ['delete']),
                    ['dashboard.view']
                );

            case RoleNames::ACCOUNTANT:
                return array_merge(
                    PermissionRegistry::namesForModulesExcludingActions(
                        ['account-type', 'account-sub-type', 'account', 'journal', 'journal-entry', 'recurring-transaction', 'expense', 'expense-category', 'admin-expense'],
                        ['delete']
                    ),
                    array_merge(
                        ['dashboard.view'],
                        self::formatVariants([
                            'general-ledger', 'trial-balance', 'journal-register',
                            'account-ledger', 'account-balance', 'day-book',
                        ])
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
                    'order.create',
                    'order.edit',
                    'order.discount.apply',
                    'order.coupon.apply',
                    'order.price.change',
                    'order.hold',
                    'order.cancel_void',
                    'order.refund.process',
                    'order.payment.credit',
                    'order.customer.change',
                    'order.reopen',
                    'expense.access',
                    'expense.view',
                ];

            case RoleNames::ORDERTAKER:
                return [
                    'pos.access',
                    'order.create',
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
