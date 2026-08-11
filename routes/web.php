<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes(['register' => false]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Route::group(['middleware' => ['auth', 'check.subscription', 'setting'], 'prefix' => 'admin'], function () {
Route::group(['middleware' => ['auth', 'setting'], 'prefix' => 'admin'], function () {
    //permissions
    Route::resource('permissions', App\Http\Controllers\Admin\PermissionController::class);
    Route::group(['prefix' => 'permissions'], function () {
        Route::post('data', [App\Http\Controllers\Admin\PermissionController::class, 'getData'])->name('permissions-data');
    });
    //roles
    Route::resource('roles', App\Http\Controllers\Admin\RoleController::class);
    Route::group(['prefix' => 'roles'], function () {
        Route::post('data', [App\Http\Controllers\Admin\RoleController::class, 'getData'])->name('roles-data');
        Route::post('reset', [App\Http\Controllers\Admin\RoleController::class, 'reset'])->name('roles-reset');
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\RoleController::class, 'byBusiness'])->name('roles-by-business');
    });
    //packages
    Route::resource('packages', App\Http\Controllers\Admin\PackageController::class);
    Route::group(['prefix' => 'packages'], function () {
        Route::post('data', [App\Http\Controllers\Admin\PackageController::class, 'getData'])->name('packages-data');
    });
    //business
    Route::resource('business', App\Http\Controllers\Admin\BusinessController::class);
    Route::group(['prefix' => 'business'], function () {
        Route::post('data', [App\Http\Controllers\Admin\BusinessController::class, 'getData'])->name('business-data');
    });
    //branch
    Route::resource('branch', App\Http\Controllers\Admin\BranchController::class);
    Route::group(['prefix' => 'branch'], function () {
        Route::post('data', [App\Http\Controllers\Admin\BranchController::class, 'getData'])->name('branch-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\BranchController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\BranchController::class, 'byBusiness'])->name('branch-by-business');
    });
    //users
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);
    Route::group(['prefix' => 'users'], function () {
        Route::post('data', [App\Http\Controllers\Admin\UserController::class, 'getData'])->name('users-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\UserController::class, 'status']);
        Route::get('change-password/{id}', [App\Http\Controllers\Admin\UserController::class, 'changePassword']);
        Route::post('change-password', [App\Http\Controllers\Admin\UserController::class, 'updatePassword']);
    });

    ////////////////////// Inventory ///////////////////////////
    //warehouse
    Route::resource('warehouse', App\Http\Controllers\Admin\WarehouseController::class);
    Route::group(['prefix' => 'warehouse'], function () {
        Route::post('data', [App\Http\Controllers\Admin\WarehouseController::class, 'getData'])->name('warehouse-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\WarehouseController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\WarehouseController::class, 'byBusiness'])->name('warehouse-by-business');
        Route::get('by-branch/{branch_id}', [App\Http\Controllers\Admin\WarehouseController::class, 'byBranch'])->name('warehouse-by-branch');
    });

    //brand
    Route::resource('brands', App\Http\Controllers\Admin\BrandController::class);
    Route::group(['prefix' => 'brands'], function () {
        Route::post('data', [App\Http\Controllers\Admin\BrandController::class, 'getData'])->name('brands-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\BrandController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\BrandController::class, 'byBusiness'])->name('brand-by-business');
    });

    //category
    Route::resource('category', App\Http\Controllers\Admin\CategoryController::class);
    Route::group(['prefix' => 'category'], function () {
        Route::post('data', [App\Http\Controllers\Admin\CategoryController::class, 'getData'])->name('category-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\CategoryController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\CategoryController::class, 'byBusiness'])->name('category-by-business');
    });

    //sub-category
    Route::resource('sub-category', App\Http\Controllers\Admin\SubCategoryController::class);
    Route::group(['prefix' => 'sub-category'], function () {
        Route::post('data', [App\Http\Controllers\Admin\SubCategoryController::class, 'getData'])->name('sub_category-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\SubCategoryController::class, 'status']);
        Route::get('by-category/{category_id}', [App\Http\Controllers\Admin\SubCategoryController::class, 'byCategory'])->name('sub-category-by-category');
    });

    //unit
    Route::resource('unit', App\Http\Controllers\Admin\UnitController::class);
    Route::group(['prefix' => 'unit'], function () {
        Route::post('data', [App\Http\Controllers\Admin\UnitController::class, 'getData'])->name('unit-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\UnitController::class, 'status']);
    });

    //product
    Route::resource('product', App\Http\Controllers\Admin\ProductController::class);
    Route::group(['prefix' => 'product'], function () {
        Route::post('data', [App\Http\Controllers\Admin\ProductController::class, 'getData'])->name('product-data');
        Route::post('change-status/{product_id}', [App\Http\Controllers\Admin\ProductController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\ProductController::class, 'byBusiness'])->name('product-by-business');
        // variations
        Route::get('variation-by-product/{product_id}', [App\Http\Controllers\Admin\ProductController::class, 'byProduct']);
        Route::get('variations/{product_id}', [App\Http\Controllers\Admin\ProductController::class, 'variations'])->name('product-variations');
        Route::post('variation/status/{variation_id}', [App\Http\Controllers\Admin\ProductController::class, 'variationStatus']);
        Route::delete('variation/delete/{variation_id}', [App\Http\Controllers\Admin\ProductController::class, 'variationDestroy']);
        // images
        Route::get('images/{productId}', [App\Http\Controllers\Admin\ProductController::class, 'getImages']);
        Route::post('image/upload', [App\Http\Controllers\Admin\ProductController::class, 'uploadImages']);
        Route::delete('image/delete/{id}', [App\Http\Controllers\Admin\ProductController::class, 'deleteImage']);
        Route::post('image/set-default/{id}', [App\Http\Controllers\Admin\ProductController::class, 'setDefaultImage']);
        Route::post('image/sorting', [App\Http\Controllers\Admin\ProductController::class, 'saveImageSorting']);
    });

    //product variation unit conversion
    Route::resource('product-variation-unit-conversion', App\Http\Controllers\Admin\ProductVariationUnitConversionController::class)->parameters(['product-variation-unit-conversion' => 'conversion']);
    Route::group(['prefix' => 'product-variation-unit-conversion'], function () {
        Route::post('data', [App\Http\Controllers\Admin\ProductVariationUnitConversionController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\ProductVariationUnitConversionController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\ProductVariationUnitConversionController::class, 'byBusiness']);
        Route::get('by-product/{product_id}', [App\Http\Controllers\Admin\ProductVariationUnitConversionController::class, 'byProduct']);
        Route::get('by-variation/{product_variation_id}', [App\Http\Controllers\Admin\ProductVariationUnitConversionController::class, 'byVariation']);
    });

    //product variation batch
    Route::resource('product-variation-batch', App\Http\Controllers\Admin\ProductVariationBatchController::class)->parameters(['product-variation-batch' => 'batch']);
    Route::group(['prefix' => 'product-variation-batch'], function () {
        Route::post('data', [App\Http\Controllers\Admin\ProductVariationBatchController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\ProductVariationBatchController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\ProductVariationBatchController::class, 'byBusiness']);
        Route::get('by-warehouse/{warehouse_id}', [App\Http\Controllers\Admin\ProductVariationBatchController::class, 'byWarehouse']);
        Route::get('by-product/{product_id}', [App\Http\Controllers\Admin\ProductVariationBatchController::class, 'byProduct']);
        Route::get('by-variation/{product_variation_id}', [App\Http\Controllers\Admin\ProductVariationBatchController::class, 'byVariation']);
    });

    // product variation stock
    Route::group(['prefix' => 'product-variation-stock'], function () {
        Route::get('/', [App\Http\Controllers\Admin\ProductVariationStockController::class, 'index']);
        Route::post('data', [App\Http\Controllers\Admin\ProductVariationStockController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\ProductVariationStockController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\ProductVariationStockController::class, 'byBusiness']);
        Route::get('by-warehouse/{warehouse_id}', [App\Http\Controllers\Admin\ProductVariationStockController::class, 'byWarehouse']);
        Route::get('by-product/{product_id}', [App\Http\Controllers\Admin\ProductVariationStockController::class, 'byProduct']);
        Route::get('by-variation/{product_variation_id}', [App\Http\Controllers\Admin\ProductVariationStockController::class, 'byVariation']);
        Route::get('history/{product_variation_stock_id}', [App\Http\Controllers\Admin\ProductVariationStockController::class, 'history']);
    });

    // product variation stock transaction
    Route::group(['prefix' => 'product-variation-stock-transaction'], function () {
        Route::get('/', [App\Http\Controllers\Admin\ProductVariationStockTransactionController::class, 'index']);
        Route::post('data', [App\Http\Controllers\Admin\ProductVariationStockTransactionController::class, 'getData']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\ProductVariationStockTransactionController::class, 'byBusiness']);
        Route::get('by-warehouse/{warehouse_id}', [App\Http\Controllers\Admin\ProductVariationStockTransactionController::class, 'byWarehouse']);
        Route::get('by-product/{product_id}', [App\Http\Controllers\Admin\ProductVariationStockTransactionController::class, 'byProduct']);
        Route::get('by-variation/{product_variation_id}', [App\Http\Controllers\Admin\ProductVariationStockTransactionController::class, 'byVariation']);
    });

    //Account Types
    Route::resource('account-type', App\Http\Controllers\Admin\AccountTypeController::class);
    Route::group(['prefix' => 'account-type'], function () {
        Route::post('data', [App\Http\Controllers\Admin\AccountTypeController::class, 'getData']);
        Route::post('reset', [App\Http\Controllers\Admin\AccountTypeController::class, 'reset']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\AccountTypeController::class, 'byBusiness']);
    });

    //Account Sub Types
    Route::resource('account-sub-type', App\Http\Controllers\Admin\AccountSubTypeController::class);
    Route::group(['prefix' => 'account-sub-type'], function () {
        Route::post('data', [App\Http\Controllers\Admin\AccountSubTypeController::class, 'getData']);
        Route::post('reset', [App\Http\Controllers\Admin\AccountSubTypeController::class, 'reset']);
        Route::get('by-account-type/{account_type_id}', [App\Http\Controllers\Admin\AccountSubTypeController::class, 'byAccountType']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\AccountSubTypeController::class, 'byBusiness']);
    });

    //account
    Route::group(['prefix' => 'account'], function () {
        Route::get('/', [App\Http\Controllers\Admin\AccountController::class, 'index']);
        Route::get('edit/{account_id}', [App\Http\Controllers\Admin\AccountController::class, 'edit']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\AccountController::class, 'status']);
        Route::delete('delete/{id}', [App\Http\Controllers\Admin\AccountController::class, 'destroy']);
        //parent
        Route::post('save-parent', [App\Http\Controllers\Admin\AccountController::class, 'storeParent']);
        //child
        Route::post('save-child', [App\Http\Controllers\Admin\AccountController::class, 'storeChild']);

        Route::get('parent-by-sub-type/{account_sub_type_id}', [App\Http\Controllers\Admin\AccountController::class, 'parentByAccountSubType']);
    });

    //journal
    Route::resource('journal', App\Http\Controllers\Admin\JournalController::class);
    Route::group(['prefix' => 'journal'], function () {
        Route::post('data', [App\Http\Controllers\Admin\JournalController::class, 'getData'])->name('journal-data');
    });

    //journal entry
    Route::group(['prefix' => 'journal-entry'], function () {
        Route::post('data', [App\Http\Controllers\Admin\JournalEntryController::class, 'getData'])->name('journal-entry-data');
        Route::get('entry-no', [App\Http\Controllers\Admin\JournalEntryController::class, 'getEntryNo'])->name('journal-entry.entry-no');
        Route::get('detail', [App\Http\Controllers\Admin\JournalEntryController::class, 'detail'])->name('journal-entry.detail');
        Route::get('{journal_entry_id}/print', [App\Http\Controllers\Admin\JournalEntryController::class, 'print'])->name('journal-entry.print');
    });
    Route::resource('journal-entry', App\Http\Controllers\Admin\JournalEntryController::class);

    //supplier
    Route::resource('supplier', App\Http\Controllers\Admin\SupplierController::class);
    Route::group(['prefix' => 'supplier'], function () {
        Route::post('data', [App\Http\Controllers\Admin\SupplierController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\SupplierController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\SupplierController::class, 'byBusiness']);
    });

    //purchase request
    Route::resource('purchase-request', App\Http\Controllers\Admin\PurchaseRequestController::class);
    Route::group(['prefix' => 'purchase-request'], function () {
        Route::post('data', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'byBusiness']);
        Route::post('send-quotation',[App\Http\Controllers\Admin\PurchaseRequestController::class,'sendQuotation']);
        Route::get('details/{purchase_request_id}', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'details']);
        Route::get('{purchase_request_id}/print', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'print'])->name('purchase-request.print');
    });

    //purchase
    Route::resource('purchase-request-quotation', App\Http\Controllers\Admin\PurchaseRequestQuotationController::class);
    Route::group(['prefix' => 'purchase-request-quotation'], function () {
        Route::post('data', [App\Http\Controllers\Admin\PurchaseRequestQuotationController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\PurchaseRequestQuotationController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\PurchaseRequestQuotationController::class, 'byBusiness']);

        Route::get('details/{purchase_request_quotation_id}', [App\Http\Controllers\Admin\PurchaseRequestQuotationController::class, 'details']);
        Route::get('detail-received/{purchase_request_id}', [App\Http\Controllers\Admin\PurchaseRequestQuotationController::class, 'getRecievedQuotationByPRId']);
        Route::get('selected-by-purchase-request/{purchase_request_id}', [App\Http\Controllers\Admin\PurchaseRequestQuotationController::class, 'selectedByPurchaseRequest']);
        Route::get('{purchase_request_quotation_id}/print', [App\Http\Controllers\Admin\PurchaseRequestQuotationController::class, 'print'])->name('purchase-request-quotation.print');
    });

    //purchase
    Route::resource('purchase', App\Http\Controllers\Admin\PurchaseController::class);
    Route::group(['prefix' => 'purchase'], function () {
        Route::post('data', [App\Http\Controllers\Admin\PurchaseController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\PurchaseController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\PurchaseController::class, 'byBusiness']);

        Route::get('details/{purchase_id}', [App\Http\Controllers\Admin\PurchaseController::class, 'details']);
        Route::get('{purchase_id}/print', [App\Http\Controllers\Admin\PurchaseController::class, 'print'])->name('purchase.print');
    });

    //good receipt note
    Route::resource('good-receipt-note', App\Http\Controllers\Admin\GoodReceiptNoteController::class);
    Route::group(['prefix' => 'good-receipt-note'], function () {
        Route::post('data', [App\Http\Controllers\Admin\GoodReceiptNoteController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\GoodReceiptNoteController::class, 'status']);
        Route::get('details/{good_receipt_note_id}', [App\Http\Controllers\Admin\GoodReceiptNoteController::class, 'details']);
        Route::get('purchase-details/{purchase_id}', [App\Http\Controllers\Admin\GoodReceiptNoteController::class, 'getPurchaseDetails']);
        Route::get('{good_receipt_note_id}/print', [App\Http\Controllers\Admin\GoodReceiptNoteController::class, 'print'])->name('good-receipt-note.print');
    });

    //purchase return
    Route::resource('purchase-return', App\Http\Controllers\Admin\PurchaseReturnController::class);
    Route::group(['prefix' => 'purchase-return'], function () {
        Route::post('data', [App\Http\Controllers\Admin\PurchaseReturnController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\PurchaseReturnController::class, 'status']);
        Route::get('details/{purchase_return_id}', [App\Http\Controllers\Admin\PurchaseReturnController::class, 'details']);
        Route::get('source-lines/{return_type}/{source_id}', [App\Http\Controllers\Admin\PurchaseReturnController::class, 'sourceLines']);
        Route::get('{purchase_return_id}/print', [App\Http\Controllers\Admin\PurchaseReturnController::class, 'print'])->name('purchase-return.print');
    });

    //supplier payment
    Route::resource('supplier-payment', App\Http\Controllers\Admin\SupplierPaymentController::class);
    Route::group(['prefix' => 'supplier-payment'], function () {
        Route::post('data', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'status']);
        Route::get('details/{supplier_payment_id}', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'details']);
        Route::get('ledger/{supplier_id}', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'supplierLedger']);
        Route::get('purchases-by-supplier/{supplier_id}', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'purchasesBySupplier']);
        Route::get('{supplier_payment_id}/print', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'print'])->name('supplier-payment.print');
    });

    //procurement reports
    Route::group(['prefix' => 'reports'], function () {
        Route::group(['prefix' => 'supplier-ledger'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\SupplierLedgerReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\SupplierLedgerReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\SupplierLedgerReportController::class, 'print'])->name('reports.supplier-ledger.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\SupplierLedgerReportController::class, 'pdf'])->name('reports.supplier-ledger.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\SupplierLedgerReportController::class, 'export'])->name('reports.supplier-ledger.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\SupplierLedgerReportController::class, 'exportCsv'])->name('reports.supplier-ledger.export-csv');
        });

        Route::group(['prefix' => 'supplier-aging'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\SupplierAgingReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\SupplierAgingReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\SupplierAgingReportController::class, 'print'])->name('reports.supplier-aging.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\SupplierAgingReportController::class, 'pdf'])->name('reports.supplier-aging.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\SupplierAgingReportController::class, 'export'])->name('reports.supplier-aging.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\SupplierAgingReportController::class, 'exportCsv'])->name('reports.supplier-aging.export-csv');
        });

        Route::group(['prefix' => 'accounts-payable'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\AccountsPayableReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\AccountsPayableReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\AccountsPayableReportController::class, 'print'])->name('reports.accounts-payable.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\AccountsPayableReportController::class, 'pdf'])->name('reports.accounts-payable.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\AccountsPayableReportController::class, 'export'])->name('reports.accounts-payable.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\AccountsPayableReportController::class, 'exportCsv'])->name('reports.accounts-payable.export-csv');
        });

        Route::group(['prefix' => 'supplier-payment-history'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\SupplierPaymentHistoryReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\SupplierPaymentHistoryReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\SupplierPaymentHistoryReportController::class, 'print'])->name('reports.supplier-payment-history.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\SupplierPaymentHistoryReportController::class, 'pdf'])->name('reports.supplier-payment-history.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\SupplierPaymentHistoryReportController::class, 'export'])->name('reports.supplier-payment-history.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\SupplierPaymentHistoryReportController::class, 'exportCsv'])->name('reports.supplier-payment-history.export-csv');
        });
    });

    //Setting
    Route::group(['prefix' => 'setting'], function () {
        Route::get('/', [App\Http\Controllers\Admin\SettingController::class, 'index']);
        Route::post('business', [App\Http\Controllers\Admin\SettingController::class, 'updateBusinessSetting'])->name('business.update');
        Route::post('accounting', [App\Http\Controllers\Admin\SettingController::class, 'updateAccountingSetting'])->name('accounting.update');
        Route::post('inventory', [App\Http\Controllers\Admin\SettingController::class, 'updateInventorySetting'])->name('inventory.update');
        Route::post('customer', [App\Http\Controllers\Admin\SettingController::class, 'updateCustomerSetting'])->name('customer.update');
        Route::post('supplier', [App\Http\Controllers\Admin\SettingController::class, 'updateSupplierSetting'])->name('supplier.update');
        Route::post('email', [App\Http\Controllers\Admin\SettingController::class, 'updateEmailSetting'])->name('email.update');
        Route::post('sms', [App\Http\Controllers\Admin\SettingController::class, 'updateSmsSetting'])->name('sms.update');
        Route::post('whatsapp', [App\Http\Controllers\Admin\SettingController::class, 'updateWhatsappSetting'])->name('whatsapp.update');
        Route::post('fbr', [App\Http\Controllers\Admin\SettingController::class, 'updateFbrSetting'])->name('fbr.update');
        Route::post('print', [App\Http\Controllers\Admin\SettingController::class, 'updatePrintSetting'])->name('print.update');
    });
});
