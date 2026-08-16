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

Route::group(['middleware' => ['auth', 'check.subscription', 'setting'], 'prefix' => 'admin'], function () {
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

    //////////////////// Subscription & Billing (Super Admin) ////////////////////
    Route::group(['prefix' => 'subscriptions', 'middleware' => ['superadmin']], function () {
        Route::get('/', [App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('subscriptions.dashboard');
        Route::post('data', [App\Http\Controllers\Admin\SubscriptionController::class, 'getData'])->name('subscriptions-data');
        Route::get('{business}', [App\Http\Controllers\Admin\SubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::get('{business}/renew', [App\Http\Controllers\Admin\SubscriptionController::class, 'renewForm'])->name('subscriptions.renew.form');
        Route::post('{business}/renew', [App\Http\Controllers\Admin\SubscriptionController::class, 'renew'])->name('subscriptions.renew');
        Route::post('{business}/suspend', [App\Http\Controllers\Admin\SubscriptionController::class, 'suspend'])->name('subscriptions.suspend');
        Route::post('{business}/reactivate', [App\Http\Controllers\Admin\SubscriptionController::class, 'reactivate'])->name('subscriptions.reactivate');
        Route::post('{business}/cancel', [App\Http\Controllers\Admin\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    });

    Route::group(['prefix' => 'subscription-renewal-requests', 'middleware' => ['superadmin']], function () {
        Route::get('/', [App\Http\Controllers\Admin\SubscriptionRenewalRequestController::class, 'index'])->name('subscription-renewal-requests.index');
        Route::post('data', [App\Http\Controllers\Admin\SubscriptionRenewalRequestController::class, 'getData'])->name('subscription-renewal-requests-data');
        Route::post('{subscription_renewal_request_id}/approve', [App\Http\Controllers\Admin\SubscriptionRenewalRequestController::class, 'approve'])->name('subscription-renewal-requests.approve');
        Route::post('{subscription_renewal_request_id}/reject', [App\Http\Controllers\Admin\SubscriptionRenewalRequestController::class, 'reject'])->name('subscription-renewal-requests.reject');
        Route::post('{subscription_renewal_request_id}/request-changes', [App\Http\Controllers\Admin\SubscriptionRenewalRequestController::class, 'requestChanges'])->name('subscription-renewal-requests.request-changes');
    });

    Route::group(['prefix' => 'subscription-invoices', 'middleware' => ['superadmin']], function () {
        Route::get('/', [App\Http\Controllers\Admin\SubscriptionInvoiceController::class, 'index'])->name('subscription-invoices.index');
        Route::post('data', [App\Http\Controllers\Admin\SubscriptionInvoiceController::class, 'getData'])->name('subscription-invoices-data');
        Route::get('{subscription_invoice_id}', [App\Http\Controllers\Admin\SubscriptionInvoiceController::class, 'show'])->name('subscription-invoices.show');
        Route::get('{subscription_invoice_id}/pdf', [App\Http\Controllers\Admin\SubscriptionInvoiceController::class, 'pdf'])->name('subscription-invoices.pdf');
        Route::post('{subscription_invoice_id}/void', [App\Http\Controllers\Admin\SubscriptionInvoiceController::class, 'void'])->name('subscription-invoices.void');
    });

    Route::group(['prefix' => 'subscription-payments', 'middleware' => ['superadmin']], function () {
        Route::post('{subscription_payment_id}/approve', [App\Http\Controllers\Admin\SubscriptionPaymentController::class, 'approve'])->name('subscription-payments.approve');
        Route::post('{subscription_payment_id}/reject', [App\Http\Controllers\Admin\SubscriptionPaymentController::class, 'reject'])->name('subscription-payments.reject');
    });

    Route::group(['prefix' => 'subscription-settings', 'middleware' => ['superadmin']], function () {
        Route::get('/', [App\Http\Controllers\Admin\SubscriptionSettingController::class, 'edit'])->name('subscription-settings.edit');
        Route::post('/', [App\Http\Controllers\Admin\SubscriptionSettingController::class, 'update'])->name('subscription-settings.update');
    });

    //////////////////// Subscription (Business Admin self-service) ////////////////////
    Route::group(['prefix' => 'my-subscription'], function () {
        Route::get('/', [App\Http\Controllers\Admin\MySubscriptionController::class, 'index'])->name('my-subscription.index');
        Route::post('renewal-requests', [App\Http\Controllers\Admin\MySubscriptionController::class, 'storeRenewalRequest'])->name('my-subscription.renewal-requests.store');
        Route::get('invoices/{subscription_invoice_id}/pdf', [App\Http\Controllers\Admin\MySubscriptionController::class, 'invoicePdf'])->name('my-subscription.invoice-pdf');
        Route::post('invoices/{subscription_invoice_id}/payments', [App\Http\Controllers\Admin\MySubscriptionController::class, 'storePayment'])->name('my-subscription.payments.store');
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
        // barcode backfill
        Route::post('barcode/backfill', [App\Http\Controllers\Admin\ProductController::class, 'backfillBarcodes'])->name('product.barcode-backfill');
    });

    //barcode & qr code
    Route::group(['prefix' => 'barcode'], function () {
        Route::get('lookup', [App\Http\Controllers\Admin\BarcodeController::class, 'lookup'])->name('barcode.lookup');
        Route::post('regenerate', [App\Http\Controllers\Admin\BarcodeController::class, 'regenerate'])->name('barcode.regenerate');
        Route::get('render/{product_variation_id}', [App\Http\Controllers\Admin\BarcodeController::class, 'render'])->name('barcode.render');
        Route::get('label-preview', [App\Http\Controllers\Admin\BarcodeController::class, 'labelPreview'])->name('barcode.label-preview');
        Route::get('label-pdf', [App\Http\Controllers\Admin\BarcodeController::class, 'labelPdf'])->name('barcode.label-pdf');
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
        Route::get('template', [App\Http\Controllers\Admin\AccountTypeController::class, 'template']);
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
        Route::get('next-code/{parent_account_id}', [App\Http\Controllers\Admin\AccountController::class, 'nextCode']);
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

    ////////////////////// Orders (centralized) ///////////////////////////
    //order type
    Route::resource('order-type', App\Http\Controllers\Admin\OrderTypeController::class);
    Route::group(['prefix' => 'order-type'], function () {
        Route::post('data', [App\Http\Controllers\Admin\OrderTypeController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\OrderTypeController::class, 'status']);
    });

    //payment method
    Route::resource('payment-method', App\Http\Controllers\Admin\PaymentMethodController::class);
    Route::group(['prefix' => 'payment-method'], function () {
        Route::post('data', [App\Http\Controllers\Admin\PaymentMethodController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\PaymentMethodController::class, 'status']);
    });

    //order source
    Route::resource('order-source', App\Http\Controllers\Admin\OrderSourceController::class);
    Route::group(['prefix' => 'order-source'], function () {
        Route::post('data', [App\Http\Controllers\Admin\OrderSourceController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\OrderSourceController::class, 'status']);
    });

    //discount
    Route::resource('discount', App\Http\Controllers\Admin\DiscountController::class);
    Route::group(['prefix' => 'discount'], function () {
        Route::post('data', [App\Http\Controllers\Admin\DiscountController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\DiscountController::class, 'status']);
    });

    //voucher
    Route::resource('voucher', App\Http\Controllers\Admin\VoucherController::class);
    Route::group(['prefix' => 'voucher'], function () {
        Route::post('data', [App\Http\Controllers\Admin\VoucherController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\VoucherController::class, 'status']);
    });

    ////////////////////// POS (operational interface only) ///////////////////////////
    Route::group(['middleware' => ['permission:pos.access']], function () {
        //pos register
        Route::resource('pos-register', App\Http\Controllers\Admin\PosRegisterController::class);
        Route::group(['prefix' => 'pos-register'], function () {
            Route::post('data', [App\Http\Controllers\Admin\PosRegisterController::class, 'getData']);
            Route::post('change-status/{id}', [App\Http\Controllers\Admin\PosRegisterController::class, 'status']);
        });

        //pos register session
        Route::group(['prefix' => 'pos-register-session'], function () {
            Route::post('data', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'getData']);
            Route::post('open', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'open']);
            Route::post('close', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'close']);
            Route::get('summary/{pos_register_session_id}', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'summary']);
            Route::post('cash-movement', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'addCashMovement']);
            Route::get('current', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'current']);
            Route::get('my-history', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'myHistory']);
        });
        Route::get('pos-register-session', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'index']);
    });

    //order (centralized - shared by POS, Website, Mobile App, API)
    Route::group(['middleware' => ['permission:pos.access']], function () {
        // Registered before the resource route below so these literal paths
        // (e.g. GET order/search-products) are matched before the
        // resource's GET order/{order} (show) wildcard would otherwise
        // swallow them.
        Route::group(['prefix' => 'order'], function () {
            Route::post('data', [App\Http\Controllers\Admin\OrderController::class, 'getData']);
            Route::post('hold', [App\Http\Controllers\Admin\OrderController::class, 'hold']);
            Route::post('resume', [App\Http\Controllers\Admin\OrderController::class, 'resume']);
            Route::post('reopen', [App\Http\Controllers\Admin\OrderController::class, 'reopen'])->middleware('permission:order.reopen');
            Route::post('cancel', [App\Http\Controllers\Admin\OrderController::class, 'cancel']);
            Route::post('complete', [App\Http\Controllers\Admin\OrderController::class, 'complete']);
            Route::post('void', [App\Http\Controllers\Admin\OrderController::class, 'void'])->middleware('permission:order.cancel_void');
            Route::get('search-products', [App\Http\Controllers\Admin\OrderController::class, 'searchProducts']);
            Route::get('filter-options/{business_id}', [App\Http\Controllers\Admin\OrderController::class, 'filterOptions']);
            Route::get('details/{order_id}', [App\Http\Controllers\Admin\OrderController::class, 'details']);
            Route::get('{order_id}/print', [App\Http\Controllers\Admin\OrderController::class, 'print'])->name('order.print');
        });
        Route::resource('order', App\Http\Controllers\Admin\OrderController::class)->except(['create', 'edit']);
    });

    //pos screen
    Route::group(['middleware' => ['permission:pos.access']], function () {
        Route::get('pos-screen', [App\Http\Controllers\Admin\PosScreenController::class, 'index'])->name('pos-screen');
        Route::post('pos-screen/context', [App\Http\Controllers\Admin\PosScreenController::class, 'selectContext'])->name('pos-screen.context');
        Route::get('pos-screen/context-options/{business_id}', [App\Http\Controllers\Admin\PosScreenController::class, 'contextOptions']);
        Route::get('pos-screen/change-context', [App\Http\Controllers\Admin\PosScreenController::class, 'changeContext'])->name('pos-screen.change-context');
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

    //opening stock
    Route::resource('opening-stock', App\Http\Controllers\Admin\OpeningStockController::class);
    Route::group(['prefix' => 'opening-stock'], function () {
        Route::post('data', [App\Http\Controllers\Admin\OpeningStockController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\OpeningStockController::class, 'status']);
        Route::get('details/{opening_stock_id}', [App\Http\Controllers\Admin\OpeningStockController::class, 'details']);
        Route::get('{opening_stock_id}/print', [App\Http\Controllers\Admin\OpeningStockController::class, 'print'])->name('opening-stock.print');
    });

    //stock taking
    Route::resource('stock-taking', App\Http\Controllers\Admin\StockTakingController::class);
    Route::group(['prefix' => 'stock-taking'], function () {
        Route::post('data', [App\Http\Controllers\Admin\StockTakingController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\StockTakingController::class, 'status']);
        Route::get('details/{stock_taking_id}', [App\Http\Controllers\Admin\StockTakingController::class, 'details']);
        Route::get('system-stock/{warehouse_id}', [App\Http\Controllers\Admin\StockTakingController::class, 'systemStock']);
        Route::get('{stock_taking_id}/print', [App\Http\Controllers\Admin\StockTakingController::class, 'print'])->name('stock-taking.print');
    });

    //transfer note
    Route::resource('transfer-note', App\Http\Controllers\Admin\TransferNoteController::class);
    Route::group(['prefix' => 'transfer-note'], function () {
        Route::post('data', [App\Http\Controllers\Admin\TransferNoteController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\TransferNoteController::class, 'status']);
        Route::get('details/{transfer_note_id}', [App\Http\Controllers\Admin\TransferNoteController::class, 'details']);
        Route::get('source-stock/{warehouse_id}', [App\Http\Controllers\Admin\TransferNoteController::class, 'sourceStock']);
        Route::get('{transfer_note_id}/print', [App\Http\Controllers\Admin\TransferNoteController::class, 'print'])->name('transfer-note.print');
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

        Route::group(['prefix' => 'purchase-return-summary'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\PurchaseReturnSummaryReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\PurchaseReturnSummaryReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\PurchaseReturnSummaryReportController::class, 'print'])->name('reports.purchase-return-summary.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\PurchaseReturnSummaryReportController::class, 'pdf'])->name('reports.purchase-return-summary.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\PurchaseReturnSummaryReportController::class, 'export'])->name('reports.purchase-return-summary.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\PurchaseReturnSummaryReportController::class, 'exportCsv'])->name('reports.purchase-return-summary.export-csv');
        });

        Route::group(['prefix' => 'purchase-return-detail'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\PurchaseReturnDetailReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\PurchaseReturnDetailReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\PurchaseReturnDetailReportController::class, 'print'])->name('reports.purchase-return-detail.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\PurchaseReturnDetailReportController::class, 'pdf'])->name('reports.purchase-return-detail.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\PurchaseReturnDetailReportController::class, 'export'])->name('reports.purchase-return-detail.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\PurchaseReturnDetailReportController::class, 'exportCsv'])->name('reports.purchase-return-detail.export-csv');
        });

        Route::group(['prefix' => 'stock-ledger'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\StockLedgerReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\StockLedgerReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\StockLedgerReportController::class, 'print'])->name('reports.stock-ledger.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\StockLedgerReportController::class, 'pdf'])->name('reports.stock-ledger.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\StockLedgerReportController::class, 'export'])->name('reports.stock-ledger.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\StockLedgerReportController::class, 'exportCsv'])->name('reports.stock-ledger.export-csv');
        });

        //accounting reports
        Route::group(['prefix' => 'general-ledger'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\GeneralLedgerReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\GeneralLedgerReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\GeneralLedgerReportController::class, 'print'])->name('reports.general-ledger.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\GeneralLedgerReportController::class, 'pdf'])->name('reports.general-ledger.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\GeneralLedgerReportController::class, 'export'])->name('reports.general-ledger.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\GeneralLedgerReportController::class, 'exportCsv'])->name('reports.general-ledger.export-csv');
        });

        Route::group(['prefix' => 'trial-balance'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\TrialBalanceReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\TrialBalanceReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\TrialBalanceReportController::class, 'print'])->name('reports.trial-balance.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\TrialBalanceReportController::class, 'pdf'])->name('reports.trial-balance.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\TrialBalanceReportController::class, 'export'])->name('reports.trial-balance.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\TrialBalanceReportController::class, 'exportCsv'])->name('reports.trial-balance.export-csv');
        });

        Route::group(['prefix' => 'journal-register'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\JournalRegisterReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\JournalRegisterReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\JournalRegisterReportController::class, 'print'])->name('reports.journal-register.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\JournalRegisterReportController::class, 'pdf'])->name('reports.journal-register.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\JournalRegisterReportController::class, 'export'])->name('reports.journal-register.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\JournalRegisterReportController::class, 'exportCsv'])->name('reports.journal-register.export-csv');
        });

        Route::group(['prefix' => 'account-ledger'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\AccountLedgerReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\AccountLedgerReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\AccountLedgerReportController::class, 'print'])->name('reports.account-ledger.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\AccountLedgerReportController::class, 'pdf'])->name('reports.account-ledger.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\AccountLedgerReportController::class, 'export'])->name('reports.account-ledger.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\AccountLedgerReportController::class, 'exportCsv'])->name('reports.account-ledger.export-csv');
        });

        Route::group(['prefix' => 'account-balance'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\AccountBalanceReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\AccountBalanceReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\AccountBalanceReportController::class, 'print'])->name('reports.account-balance.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\AccountBalanceReportController::class, 'pdf'])->name('reports.account-balance.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\AccountBalanceReportController::class, 'export'])->name('reports.account-balance.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\AccountBalanceReportController::class, 'exportCsv'])->name('reports.account-balance.export-csv');
        });

        Route::group(['prefix' => 'day-book'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\DayBookReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\DayBookReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\DayBookReportController::class, 'print'])->name('reports.day-book.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\DayBookReportController::class, 'pdf'])->name('reports.day-book.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\DayBookReportController::class, 'export'])->name('reports.day-book.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\DayBookReportController::class, 'exportCsv'])->name('reports.day-book.export-csv');
        });

        Route::group(['prefix' => 'cash-bank-ledger'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\CashBankLedgerReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\CashBankLedgerReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\CashBankLedgerReportController::class, 'print'])->name('reports.cash-bank-ledger.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\CashBankLedgerReportController::class, 'pdf'])->name('reports.cash-bank-ledger.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\CashBankLedgerReportController::class, 'export'])->name('reports.cash-bank-ledger.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\CashBankLedgerReportController::class, 'exportCsv'])->name('reports.cash-bank-ledger.export-csv');
        });

        Route::group(['prefix' => 'income-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\IncomeReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\IncomeReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\IncomeReportController::class, 'print'])->name('reports.income-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\IncomeReportController::class, 'pdf'])->name('reports.income-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\IncomeReportController::class, 'export'])->name('reports.income-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\IncomeReportController::class, 'exportCsv'])->name('reports.income-report.export-csv');
        });

        Route::group(['prefix' => 'expense-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'print'])->name('reports.expense-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'pdf'])->name('reports.expense-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'export'])->name('reports.expense-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'exportCsv'])->name('reports.expense-report.export-csv');
        });

        Route::group(['prefix' => 'tax-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\TaxReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\TaxReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\TaxReportController::class, 'print'])->name('reports.tax-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\TaxReportController::class, 'pdf'])->name('reports.tax-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\TaxReportController::class, 'export'])->name('reports.tax-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\TaxReportController::class, 'exportCsv'])->name('reports.tax-report.export-csv');
        });

        Route::group(['prefix' => 'equity-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\EquityReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\EquityReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\EquityReportController::class, 'print'])->name('reports.equity-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\EquityReportController::class, 'pdf'])->name('reports.equity-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\EquityReportController::class, 'export'])->name('reports.equity-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\EquityReportController::class, 'exportCsv'])->name('reports.equity-report.export-csv');
        });

        // Profit & Loss and Balance Sheet render as computed statements (no DataTables data() endpoint).
        Route::group(['prefix' => 'profit-loss'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\ProfitLossReportController::class, 'index']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\ProfitLossReportController::class, 'print'])->name('reports.profit-loss.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\ProfitLossReportController::class, 'pdf'])->name('reports.profit-loss.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\ProfitLossReportController::class, 'export'])->name('reports.profit-loss.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\ProfitLossReportController::class, 'exportCsv'])->name('reports.profit-loss.export-csv');
        });

        Route::group(['prefix' => 'balance-sheet'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\BalanceSheetReportController::class, 'index']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\BalanceSheetReportController::class, 'print'])->name('reports.balance-sheet.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\BalanceSheetReportController::class, 'pdf'])->name('reports.balance-sheet.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\BalanceSheetReportController::class, 'export'])->name('reports.balance-sheet.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\BalanceSheetReportController::class, 'exportCsv'])->name('reports.balance-sheet.export-csv');
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
        Route::post('pos', [App\Http\Controllers\Admin\SettingController::class, 'updatePosSetting'])->name('pos.update');
        Route::post('pra', [App\Http\Controllers\Admin\SettingController::class, 'updatePraSetting'])->name('pra.update');
        Route::post('print', [App\Http\Controllers\Admin\SettingController::class, 'updatePrintSetting'])->name('print.update');
        Route::post('barcode', [App\Http\Controllers\Admin\SettingController::class, 'updateBarcodeSetting'])->name('barcode.update');
        Route::post('theme', [App\Http\Controllers\Admin\SettingController::class, 'updateThemeSetting'])->name('theme.update');
        Route::post('theme/preset', [App\Http\Controllers\Admin\SettingController::class, 'applyThemePreset'])->name('theme.preset');
    });
});
