<?php

namespace App\Support\ImportExport;

use App\Services\ImportExport\Contracts\ImportExportDefinitionContract;
use App\Services\ImportExport\Modules\Asset\AssetImportExportDefinition;
use App\Services\ImportExport\Modules\AssetAllocation\AssetAllocationImportExportDefinition;
use App\Services\ImportExport\Modules\Attendance\AttendanceImportExportDefinition;
use App\Services\ImportExport\Modules\Brand\BrandImportExportDefinition;
use App\Services\ImportExport\Modules\Category\CategoryImportExportDefinition;
use App\Services\ImportExport\Modules\Customer\CustomerImportExportDefinition;
use App\Services\ImportExport\Modules\CustomerPayment\CustomerPaymentImportExportDefinition;
use App\Services\ImportExport\Modules\Department\DepartmentImportExportDefinition;
use App\Services\ImportExport\Modules\Designation\DesignationImportExportDefinition;
use App\Services\ImportExport\Modules\Discount\DiscountImportExportDefinition;
use App\Services\ImportExport\Modules\Employee\EmployeeImportExportDefinition;
use App\Services\ImportExport\Modules\EmployeeAdvance\EmployeeAdvanceImportExportDefinition;
use App\Services\ImportExport\Modules\Expense\ExpenseImportExportDefinition;
use App\Services\ImportExport\Modules\ExpenseCategory\ExpenseCategoryImportExportDefinition;
use App\Services\ImportExport\Modules\JournalEntry\JournalEntryImportExportDefinition;
use App\Services\ImportExport\Modules\OpeningStock\OpeningStockImportExportDefinition;
use App\Services\ImportExport\Modules\Order\OrderImportExportDefinition;
use App\Services\ImportExport\Modules\Product\ProductImportExportDefinition;
use App\Services\ImportExport\Modules\PurchaseRequest\PurchaseRequestImportExportDefinition;
use App\Services\ImportExport\Modules\RecurringTransaction\RecurringTransactionImportExportDefinition;
use App\Services\ImportExport\Modules\Shift\ShiftImportExportDefinition;
use App\Services\ImportExport\Modules\SubCategory\SubCategoryImportExportDefinition;
use App\Services\ImportExport\Modules\Supplier\SupplierImportExportDefinition;
use App\Services\ImportExport\Modules\SupplierPayment\SupplierPaymentImportExportDefinition;
use App\Services\ImportExport\Modules\TransferNote\TransferNoteImportExportDefinition;
use App\Services\ImportExport\Modules\User\UserImportExportDefinition;
use App\Services\ImportExport\Modules\Voucher\VoucherImportExportDefinition;
use App\Services\ImportExport\Modules\Warehouse\WarehouseImportExportDefinition;
use InvalidArgumentException;

/**
 * Single source of truth mapping a module key to its Import/Export
 * Definition class (or, where one class serves two modules, a factory
 * closure) - mirrors PermissionRegistry's role as the one place that lists
 * every module. Add one line here whenever a module's Definition class is
 * created.
 */
class ImportExportModuleRegistry
{
    public static function definitions(): array
    {
        return [
            'user' => UserImportExportDefinition::class,
            'warehouse' => WarehouseImportExportDefinition::class,
            'brand' => BrandImportExportDefinition::class,
            'category' => CategoryImportExportDefinition::class,
            'sub-category' => SubCategoryImportExportDefinition::class,
            'product' => ProductImportExportDefinition::class,
            'opening-stock' => OpeningStockImportExportDefinition::class,
            'transfer-note' => TransferNoteImportExportDefinition::class,
            'journal-entry' => JournalEntryImportExportDefinition::class,
            'recurring-transaction' => RecurringTransactionImportExportDefinition::class,
            'expense-category' => ExpenseCategoryImportExportDefinition::class,
            'expense' => fn () => new ExpenseImportExportDefinition('expense', 'Expenses', false),
            'admin-expense' => fn () => new ExpenseImportExportDefinition('admin-expense', 'Admin Expenses', true),
            'department' => DepartmentImportExportDefinition::class,
            'designation' => DesignationImportExportDefinition::class,
            'shift' => ShiftImportExportDefinition::class,
            'employee' => EmployeeImportExportDefinition::class,
            'attendance' => AttendanceImportExportDefinition::class,
            'employee-advance' => EmployeeAdvanceImportExportDefinition::class,
            'asset' => AssetImportExportDefinition::class,
            'asset-allocation' => AssetAllocationImportExportDefinition::class,
            'supplier' => SupplierImportExportDefinition::class,
            'supplier-payment' => SupplierPaymentImportExportDefinition::class,
            'customer' => CustomerImportExportDefinition::class,
            'customer-payment' => CustomerPaymentImportExportDefinition::class,
            'purchase-request' => PurchaseRequestImportExportDefinition::class,
            'order' => OrderImportExportDefinition::class,
            'discount' => DiscountImportExportDefinition::class,
            'voucher' => VoucherImportExportDefinition::class,
        ];
    }

    public static function resolve(string $moduleKey): ImportExportDefinitionContract
    {
        $entry = self::definitions()[$moduleKey] ?? null;

        if (!$entry) {
            throw new InvalidArgumentException("No import/export definition registered for module [{$moduleKey}].");
        }

        return $entry instanceof \Closure ? $entry() : app($entry);
    }
}
