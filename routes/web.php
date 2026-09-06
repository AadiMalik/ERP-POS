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

Route::get('password/otp', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showOtpForm'])->name('password.otp');
Route::post('password/otp', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'resetWithOtp'])->name('password.otp.reset');
Route::post('password/otp/resend', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'resendOtp'])->name('password.otp.resend');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/documentation', fn () => redirect()->route('documentation.index'));

Route::group(['middleware' => ['auth', 'check.subscription', 'setting', 'must-change-password'], 'prefix' => 'admin'], function () {
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

    //////////////////// Dukanaz Intro CMS (Super Admin only) ////////////////////
    Route::group(['prefix' => 'intro', 'middleware' => ['superadmin'], 'as' => 'intro.'], function () {
        Route::get('modules', [App\Http\Controllers\Admin\Intro\ModuleController::class, 'index'])->name('modules.index');
        Route::post('modules/data', [App\Http\Controllers\Admin\Intro\ModuleController::class, 'getData'])->name('modules-data');
        Route::get('modules/{id}/edit', [App\Http\Controllers\Admin\Intro\ModuleController::class, 'show'])->name('modules.edit');
        Route::get('modules/{id}', [App\Http\Controllers\Admin\Intro\ModuleController::class, 'show'])->name('modules.show');
        Route::post('modules', [App\Http\Controllers\Admin\Intro\ModuleController::class, 'store'])->name('modules.store');
        Route::post('modules/change-status/{id}', [App\Http\Controllers\Admin\Intro\ModuleController::class, 'status'])->name('modules.status');
        Route::post('modules/toggle-feature/{id}', [App\Http\Controllers\Admin\Intro\ModuleController::class, 'toggleFeature'])->name('modules.feature');
        Route::delete('modules/{id}', [App\Http\Controllers\Admin\Intro\ModuleController::class, 'destroy'])->name('modules.destroy');

        Route::get('blog-categories', [App\Http\Controllers\Admin\Intro\BlogCategoryController::class, 'index'])->name('blog-categories.index');
        Route::post('blog-categories/data', [App\Http\Controllers\Admin\Intro\BlogCategoryController::class, 'getData'])->name('blog-categories-data');
        Route::get('blog-categories/{id}/edit', [App\Http\Controllers\Admin\Intro\BlogCategoryController::class, 'show'])->name('blog-categories.edit');
        Route::get('blog-categories/{id}', [App\Http\Controllers\Admin\Intro\BlogCategoryController::class, 'show'])->name('blog-categories.show');
        Route::post('blog-categories', [App\Http\Controllers\Admin\Intro\BlogCategoryController::class, 'store'])->name('blog-categories.store');
        Route::post('blog-categories/change-status/{id}', [App\Http\Controllers\Admin\Intro\BlogCategoryController::class, 'status']);
        Route::delete('blog-categories/{id}', [App\Http\Controllers\Admin\Intro\BlogCategoryController::class, 'destroy']);

        Route::get('blog-tags', [App\Http\Controllers\Admin\Intro\BlogTagController::class, 'index'])->name('blog-tags.index');
        Route::post('blog-tags/data', [App\Http\Controllers\Admin\Intro\BlogTagController::class, 'getData'])->name('blog-tags-data');
        Route::get('blog-tags/{id}/edit', [App\Http\Controllers\Admin\Intro\BlogTagController::class, 'show']);
        Route::get('blog-tags/{id}', [App\Http\Controllers\Admin\Intro\BlogTagController::class, 'show']);
        Route::post('blog-tags', [App\Http\Controllers\Admin\Intro\BlogTagController::class, 'store']);
        Route::post('blog-tags/change-status/{id}', [App\Http\Controllers\Admin\Intro\BlogTagController::class, 'status']);
        Route::delete('blog-tags/{id}', [App\Http\Controllers\Admin\Intro\BlogTagController::class, 'destroy']);

        Route::get('blogs', [App\Http\Controllers\Admin\Intro\BlogController::class, 'index'])->name('blogs.index');
        Route::post('blogs/data', [App\Http\Controllers\Admin\Intro\BlogController::class, 'getData'])->name('blogs-data');
        Route::get('blogs/{id}/edit', [App\Http\Controllers\Admin\Intro\BlogController::class, 'show']);
        Route::get('blogs/{id}', [App\Http\Controllers\Admin\Intro\BlogController::class, 'show']);
        Route::post('blogs', [App\Http\Controllers\Admin\Intro\BlogController::class, 'store']);
        Route::delete('blogs/{id}', [App\Http\Controllers\Admin\Intro\BlogController::class, 'destroy']);

        Route::get('blog-comments', [App\Http\Controllers\Admin\Intro\BlogCommentController::class, 'index'])->name('blog-comments.index');
        Route::post('blog-comments/data', [App\Http\Controllers\Admin\Intro\BlogCommentController::class, 'getData'])->name('blog-comments-data');
        Route::get('blog-comments/{id}/edit', [App\Http\Controllers\Admin\Intro\BlogCommentController::class, 'show']);
        Route::get('blog-comments/{id}', [App\Http\Controllers\Admin\Intro\BlogCommentController::class, 'show']);
        Route::post('blog-comments/{id}/moderate', [App\Http\Controllers\Admin\Intro\BlogCommentController::class, 'moderate']);
        Route::delete('blog-comments/{id}', [App\Http\Controllers\Admin\Intro\BlogCommentController::class, 'destroy']);

        Route::get('testimonials', [App\Http\Controllers\Admin\Intro\TestimonialController::class, 'index'])->name('testimonials.index');
        Route::post('testimonials/data', [App\Http\Controllers\Admin\Intro\TestimonialController::class, 'getData'])->name('testimonials-data');
        Route::get('testimonials/{id}/edit', [App\Http\Controllers\Admin\Intro\TestimonialController::class, 'show']);
        Route::get('testimonials/{id}', [App\Http\Controllers\Admin\Intro\TestimonialController::class, 'show']);
        Route::post('testimonials', [App\Http\Controllers\Admin\Intro\TestimonialController::class, 'store']);
        Route::post('testimonials/change-status/{id}', [App\Http\Controllers\Admin\Intro\TestimonialController::class, 'status']);
        Route::delete('testimonials/{id}', [App\Http\Controllers\Admin\Intro\TestimonialController::class, 'destroy']);

        Route::get('contact-inquiries', [App\Http\Controllers\Admin\Intro\ContactInquiryController::class, 'index'])->name('contact-inquiries.index');
        Route::post('contact-inquiries/data', [App\Http\Controllers\Admin\Intro\ContactInquiryController::class, 'getData'])->name('contact-inquiries-data');
        Route::get('contact-inquiries/{id}/edit', [App\Http\Controllers\Admin\Intro\ContactInquiryController::class, 'show']);
        Route::get('contact-inquiries/{id}', [App\Http\Controllers\Admin\Intro\ContactInquiryController::class, 'show']);
        Route::post('contact-inquiries/{id}/reply', [App\Http\Controllers\Admin\Intro\ContactInquiryController::class, 'reply']);
        Route::post('contact-inquiries/{id}/status', [App\Http\Controllers\Admin\Intro\ContactInquiryController::class, 'updateStatus']);
        Route::post('contact-inquiries/{id}/register-business', [App\Http\Controllers\Admin\Intro\ContactInquiryController::class, 'registerBusiness'])->name('contact-inquiries.register-business');
        Route::post('contact-inquiries/{id}/payment', [App\Http\Controllers\Admin\Intro\ContactInquiryController::class, 'updatePayment'])->name('contact-inquiries.payment');
        Route::post('contact-inquiries/{id}/activate', [App\Http\Controllers\Admin\Intro\ContactInquiryController::class, 'activate'])->name('contact-inquiries.activate');
        Route::delete('contact-inquiries/{id}', [App\Http\Controllers\Admin\Intro\ContactInquiryController::class, 'destroy']);

        Route::get('website-settings', [App\Http\Controllers\Admin\Intro\WebsiteSettingController::class, 'index'])->name('website-settings.index');
        Route::get('website-settings/data', [App\Http\Controllers\Admin\Intro\WebsiteSettingController::class, 'show'])->name('website-settings.show');
        Route::post('website-settings', [App\Http\Controllers\Admin\Intro\WebsiteSettingController::class, 'update'])->name('website-settings.update');

        Route::get('navigation', [App\Http\Controllers\Admin\Intro\NavigationController::class, 'index'])->name('navigation.index');
        Route::post('navigation/data', [App\Http\Controllers\Admin\Intro\NavigationController::class, 'getData'])->name('navigation-data');
        Route::get('navigation/{id}/edit', [App\Http\Controllers\Admin\Intro\NavigationController::class, 'show']);
        Route::get('navigation/{id}', [App\Http\Controllers\Admin\Intro\NavigationController::class, 'show']);
        Route::post('navigation', [App\Http\Controllers\Admin\Intro\NavigationController::class, 'store']);
        Route::post('navigation/change-status/{id}', [App\Http\Controllers\Admin\Intro\NavigationController::class, 'status']);
        Route::delete('navigation/{id}', [App\Http\Controllers\Admin\Intro\NavigationController::class, 'destroy']);

        Route::get('media', [App\Http\Controllers\Admin\Intro\MediaController::class, 'index'])->name('media.index');
        Route::post('media/data', [App\Http\Controllers\Admin\Intro\MediaController::class, 'getData'])->name('media-data');
        Route::get('media/{id}/edit', [App\Http\Controllers\Admin\Intro\MediaController::class, 'show']);
        Route::get('media/{id}', [App\Http\Controllers\Admin\Intro\MediaController::class, 'show']);
        Route::post('media', [App\Http\Controllers\Admin\Intro\MediaController::class, 'store']);
        Route::delete('media/{id}', [App\Http\Controllers\Admin\Intro\MediaController::class, 'destroy']);

        Route::get('homepage-sections', [App\Http\Controllers\Admin\Intro\HomepageSectionController::class, 'index'])->name('homepage-sections.index');
        Route::post('homepage-sections/data', [App\Http\Controllers\Admin\Intro\HomepageSectionController::class, 'getData'])->name('homepage-sections-data');
        Route::get('homepage-sections/{id}/edit', [App\Http\Controllers\Admin\Intro\HomepageSectionController::class, 'show'])->name('homepage-sections.edit');
        Route::get('homepage-sections/{id}', [App\Http\Controllers\Admin\Intro\HomepageSectionController::class, 'show']);
        Route::post('homepage-sections', [App\Http\Controllers\Admin\Intro\HomepageSectionController::class, 'store']);
        Route::post('homepage-sections/change-status/{id}', [App\Http\Controllers\Admin\Intro\HomepageSectionController::class, 'status']);
        Route::post('homepage-sections/toggle-enabled/{id}', [App\Http\Controllers\Admin\Intro\HomepageSectionController::class, 'toggleEnabled']);
        Route::delete('homepage-sections/{id}', [App\Http\Controllers\Admin\Intro\HomepageSectionController::class, 'destroy']);

        Route::get('pages', [App\Http\Controllers\Admin\Intro\PageController::class, 'index'])->name('pages.index');
        Route::post('pages/data', [App\Http\Controllers\Admin\Intro\PageController::class, 'getData'])->name('pages-data');
        Route::get('pages/{id}/edit', [App\Http\Controllers\Admin\Intro\PageController::class, 'show']);
        Route::get('pages/{id}', [App\Http\Controllers\Admin\Intro\PageController::class, 'show']);
        Route::post('pages', [App\Http\Controllers\Admin\Intro\PageController::class, 'store']);
        Route::delete('pages/{id}', [App\Http\Controllers\Admin\Intro\PageController::class, 'destroy']);

        Route::get('business-registrations', [App\Http\Controllers\Admin\Intro\BusinessRegistrationController::class, 'index'])->name('business-registrations.index');
        Route::post('business-registrations/data', [App\Http\Controllers\Admin\Intro\BusinessRegistrationController::class, 'getData'])->name('business-registrations-data');
        Route::get('business-registrations/{id}/edit', [App\Http\Controllers\Admin\Intro\BusinessRegistrationController::class, 'show']);
        Route::get('business-registrations/{id}', [App\Http\Controllers\Admin\Intro\BusinessRegistrationController::class, 'show']);
        Route::post('business-registrations/{id}/status', [App\Http\Controllers\Admin\Intro\BusinessRegistrationController::class, 'updateStatus']);
        Route::post('business-registrations/{id}/approve-payment', [App\Http\Controllers\Admin\Intro\BusinessRegistrationController::class, 'approvePayment'])->name('business-registrations.approve-payment');
        Route::post('business-registrations/{id}/reject-payment', [App\Http\Controllers\Admin\Intro\BusinessRegistrationController::class, 'rejectPayment'])->name('business-registrations.reject-payment');
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
        Route::get('pending-count', [App\Http\Controllers\Admin\SubscriptionInvoiceController::class, 'pendingCount'])->name('subscription-invoices.pending-count');
        Route::get('{subscription_invoice_id}', [App\Http\Controllers\Admin\SubscriptionInvoiceController::class, 'show'])->name('subscription-invoices.show');
        Route::get('{subscription_invoice_id}/pdf', [App\Http\Controllers\Admin\SubscriptionInvoiceController::class, 'pdf'])->name('subscription-invoices.pdf');
        Route::post('{subscription_invoice_id}/void', [App\Http\Controllers\Admin\SubscriptionInvoiceController::class, 'void'])->name('subscription-invoices.void');
        Route::delete('{subscription_invoice_id}', [App\Http\Controllers\Admin\SubscriptionInvoiceController::class, 'destroy'])->name('subscription-invoices.destroy');
    });

    Route::group(['prefix' => 'subscription-payments', 'middleware' => ['superadmin']], function () {
        Route::post('{subscription_payment_id}/approve', [App\Http\Controllers\Admin\SubscriptionPaymentController::class, 'approve'])->name('subscription-payments.approve');
        Route::post('{subscription_payment_id}/reject', [App\Http\Controllers\Admin\SubscriptionPaymentController::class, 'reject'])->name('subscription-payments.reject');
    });

    Route::group(['prefix' => 'subscription-settings', 'middleware' => ['superadmin']], function () {
        Route::get('/', [App\Http\Controllers\Admin\SubscriptionSettingController::class, 'edit'])->name('subscription-settings.edit');
        Route::post('/', [App\Http\Controllers\Admin\SubscriptionSettingController::class, 'update'])->name('subscription-settings.update');
    });

    //////////////////// Business Access Control (Super Admin) ////////////////////
    Route::group(['prefix' => 'business-access-control', 'middleware' => ['superadmin']], function () {
        Route::get('/', [App\Http\Controllers\Admin\BusinessAccessControlController::class, 'index'])->name('business-access-control.index');
        Route::post('data', [App\Http\Controllers\Admin\BusinessAccessControlController::class, 'getData'])->name('business-access-control-data');
        Route::post('{business_id}/{platform}', [App\Http\Controllers\Admin\BusinessAccessControlController::class, 'toggle'])
            ->where('platform', 'erp|storefront|pos|offline-pos')
            ->name('business-access-control.toggle');
    });

    //////////////////// System Feature Controls (Super Admin) ////////////////////
    Route::group(['prefix' => 'system-feature-flags', 'middleware' => ['superadmin']], function () {
        Route::get('/', [App\Http\Controllers\Admin\SystemFeatureFlagController::class, 'index'])->name('system-feature-flags.index');
        Route::post('data', [App\Http\Controllers\Admin\SystemFeatureFlagController::class, 'getData'])->name('system-feature-flags-data');
        Route::post('toggle/{id}', [App\Http\Controllers\Admin\SystemFeatureFlagController::class, 'toggle'])->name('system-feature-flags.toggle');
    });

    //////////////////// Backup, Restore & Disaster Recovery (Super Admin) ////////////////////
    Route::group(['prefix' => 'backups', 'middleware' => ['superadmin']], function () {
        Route::get('/', [App\Http\Controllers\Admin\BackupController::class, 'index'])->name('backups.index');
        Route::post('/', [App\Http\Controllers\Admin\BackupController::class, 'store'])->name('backups.store');
        Route::get('settings', [App\Http\Controllers\Admin\BackupController::class, 'settingsEdit'])->name('backup-settings.edit');
        Route::post('settings', [App\Http\Controllers\Admin\BackupController::class, 'settingsUpdate'])->name('backup-settings.update');
        Route::post('cleanup', [App\Http\Controllers\Admin\BackupController::class, 'cleanup'])->name('backups.cleanup');
        Route::get('{id}/download', [App\Http\Controllers\Admin\BackupController::class, 'download'])->name('backups.download');
        Route::delete('{id}', [App\Http\Controllers\Admin\BackupController::class, 'destroy'])->name('backups.destroy');
        Route::post('{id}/restore', [App\Http\Controllers\Admin\BackupController::class, 'restore'])->name('backups.restore');
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
    Route::resource('users', App\Http\Controllers\Admin\UserController::class)->except(['show']);
    Route::group(['prefix' => 'users'], function () {
        Route::post('data', [App\Http\Controllers\Admin\UserController::class, 'getData'])->name('users-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\UserController::class, 'status']);
        Route::get('change-password/{id}', [App\Http\Controllers\Admin\UserController::class, 'changePassword']);
        Route::post('change-password', [App\Http\Controllers\Admin\UserController::class, 'updatePassword']);
        Route::get('import/sample', [App\Http\Controllers\Admin\UserController::class, 'importSample'])->name('user-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\UserController::class, 'importPreview'])->name('user-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\UserController::class, 'importConfirm'])->name('user-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\UserController::class, 'export'])->name('user-export');
    });

    //customer (ungated by any module: middleware - core CRM data, mirrors 'users')
    // 'update' is excluded (like 'show'): edits are submitted via POST to
    // store(), same as SupplierController; this also avoids colliding with
    // the unrelated 'customer.update' route name already used by
    // SettingController::updateCustomerSetting().
    Route::resource('customer', App\Http\Controllers\Admin\CustomerController::class)->except(['show', 'update']);
    Route::group(['prefix' => 'customer'], function () {
        Route::get('{user_id}/show', [App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('customer.show');
        Route::post('data', [App\Http\Controllers\Admin\CustomerController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\CustomerController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\CustomerController::class, 'byBusiness']);
        Route::get('import/sample', [App\Http\Controllers\Admin\CustomerController::class, 'importSample'])->name('customer-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\CustomerController::class, 'importPreview'])->name('customer-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\CustomerController::class, 'importConfirm'])->name('customer-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\CustomerController::class, 'export'])->name('customer-export');
    });

    //generic "View JV" / "Stock Consumption Details" popups - reused from Orders,
    //Purchases, Customer/Supplier Payments, Expenses, etc. Ungated by any
    //module: middleware (must work regardless of which optional modules are
    //enabled); business-scoping is enforced inside each controller.
    Route::post('journal-voucher/view', [App\Http\Controllers\Admin\JournalVoucherViewController::class, 'show'])->name('journal-voucher.view');
    Route::post('stock-consumption/view', [App\Http\Controllers\Admin\StockConsumptionViewController::class, 'show'])->name('stock-consumption.view');

    //my profile (self-service - every authenticated user manages their own record, no permission gate)
    Route::group(['prefix' => 'profile'], function () {
        Route::get('/', [App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
        Route::post('password', [App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.password.update');
    });
    Route::get('force-password-change', [App\Http\Controllers\Admin\ProfileController::class, 'forceChangeForm'])->name('force-password-change');

    //global header search (each result group is gated by that module's own .view permission, not a separate one)
    Route::get('search/global', [App\Http\Controllers\Admin\SearchController::class, 'globalSearch'])->name('search.global');

    ////////////////////// HRM & Payroll ///////////////////////////
    Route::group(['middleware' => ['module:hrm']], function () {
    //departments
    Route::resource('department', App\Http\Controllers\Admin\Hrm\DepartmentController::class)->except(['show']);
    Route::post('department/data', [App\Http\Controllers\Admin\Hrm\DepartmentController::class, 'getData'])->name('department-data');
    Route::get('department/import/sample', [App\Http\Controllers\Admin\Hrm\DepartmentController::class, 'importSample'])->name('department-import-sample');
    Route::post('department/import/preview', [App\Http\Controllers\Admin\Hrm\DepartmentController::class, 'importPreview'])->name('department-import-preview');
    Route::post('department/import/confirm', [App\Http\Controllers\Admin\Hrm\DepartmentController::class, 'importConfirm'])->name('department-import-confirm');
    Route::get('department/export', [App\Http\Controllers\Admin\Hrm\DepartmentController::class, 'export'])->name('department-export');
    //designations
    Route::resource('designation', App\Http\Controllers\Admin\Hrm\DesignationController::class)->except(['show']);
    Route::post('designation/data', [App\Http\Controllers\Admin\Hrm\DesignationController::class, 'getData'])->name('designation-data');
    Route::get('designation/import/sample', [App\Http\Controllers\Admin\Hrm\DesignationController::class, 'importSample'])->name('designation-import-sample');
    Route::post('designation/import/preview', [App\Http\Controllers\Admin\Hrm\DesignationController::class, 'importPreview'])->name('designation-import-preview');
    Route::post('designation/import/confirm', [App\Http\Controllers\Admin\Hrm\DesignationController::class, 'importConfirm'])->name('designation-import-confirm');
    Route::get('designation/export', [App\Http\Controllers\Admin\Hrm\DesignationController::class, 'export'])->name('designation-export');
    //shifts
    Route::resource('shift', App\Http\Controllers\Admin\Hrm\ShiftController::class)->except(['show']);
    Route::post('shift/data', [App\Http\Controllers\Admin\Hrm\ShiftController::class, 'getData'])->name('shift-data');
    Route::get('shift/import/sample', [App\Http\Controllers\Admin\Hrm\ShiftController::class, 'importSample'])->name('shift-import-sample');
    Route::post('shift/import/preview', [App\Http\Controllers\Admin\Hrm\ShiftController::class, 'importPreview'])->name('shift-import-preview');
    Route::post('shift/import/confirm', [App\Http\Controllers\Admin\Hrm\ShiftController::class, 'importConfirm'])->name('shift-import-confirm');
    Route::get('shift/export', [App\Http\Controllers\Admin\Hrm\ShiftController::class, 'export'])->name('shift-export');
    //employees
    Route::resource('employee', App\Http\Controllers\Admin\Hrm\EmployeeController::class)->except(['show']);
    Route::group(['prefix' => 'employee'], function () {
        Route::post('data', [App\Http\Controllers\Admin\Hrm\EmployeeController::class, 'getData'])->name('employee-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\Hrm\EmployeeController::class, 'status']);
        Route::post('{employee_id}/documents', [App\Http\Controllers\Admin\Hrm\EmployeeController::class, 'storeDocument'])->name('employee.documents.store');
        Route::get('import/sample', [App\Http\Controllers\Admin\Hrm\EmployeeController::class, 'importSample'])->name('employee-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\Hrm\EmployeeController::class, 'importPreview'])->name('employee-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\Hrm\EmployeeController::class, 'importConfirm'])->name('employee-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\Hrm\EmployeeController::class, 'export'])->name('employee-export');
    });
    Route::delete('employee-document/{employee_document_id}', [App\Http\Controllers\Admin\Hrm\EmployeeController::class, 'destroyDocument']);
    //attendance
    Route::get('attendance/report', [App\Http\Controllers\Admin\Hrm\AttendanceController::class, 'report'])->name('attendance.report');
    Route::resource('attendance', App\Http\Controllers\Admin\Hrm\AttendanceController::class)->except(['show']);
    Route::post('attendance/data', [App\Http\Controllers\Admin\Hrm\AttendanceController::class, 'getData'])->name('attendance-data');
    Route::get('attendance/import/sample', [App\Http\Controllers\Admin\Hrm\AttendanceController::class, 'importSample'])->name('attendance-import-sample');
    Route::post('attendance/import/preview', [App\Http\Controllers\Admin\Hrm\AttendanceController::class, 'importPreview'])->name('attendance-import-preview');
    Route::post('attendance/import/confirm', [App\Http\Controllers\Admin\Hrm\AttendanceController::class, 'importConfirm'])->name('attendance-import-confirm');
    Route::get('attendance/export', [App\Http\Controllers\Admin\Hrm\AttendanceController::class, 'export'])->name('attendance-export');
    //leave types
    Route::resource('leave-type', App\Http\Controllers\Admin\Hrm\LeaveTypeController::class);
    Route::post('leave-type/data', [App\Http\Controllers\Admin\Hrm\LeaveTypeController::class, 'getData'])->name('leave-type-data');
    //leave requests
    Route::resource('leave-request', App\Http\Controllers\Admin\Hrm\LeaveRequestController::class);
    Route::post('leave-request/data', [App\Http\Controllers\Admin\Hrm\LeaveRequestController::class, 'getData'])->name('leave-request-data');
    Route::post('leave-request/{leave_request_id}/decide', [App\Http\Controllers\Admin\Hrm\LeaveRequestController::class, 'decide'])->name('leave-request.decide');
    //salary components
    Route::resource('salary-component', App\Http\Controllers\Admin\Hrm\SalaryComponentController::class);
    Route::post('salary-component/data', [App\Http\Controllers\Admin\Hrm\SalaryComponentController::class, 'getData'])->name('salary-component-data');
    //salary structures
    Route::get('salary-structure', [App\Http\Controllers\Admin\Hrm\EmployeeSalaryStructureController::class, 'index'])->name('salary-structure.index');
    Route::get('salary-structure/{employee_id}', [App\Http\Controllers\Admin\Hrm\EmployeeSalaryStructureController::class, 'manage'])->name('salary-structure.manage');
    Route::post('salary-structure/{employee_id}', [App\Http\Controllers\Admin\Hrm\EmployeeSalaryStructureController::class, 'store'])->name('salary-structure.store');
    Route::delete('salary-structure-version/{employee_salary_structure_id}', [App\Http\Controllers\Admin\Hrm\EmployeeSalaryStructureController::class, 'destroy']);
    //employee advances
    Route::resource('employee-advance', App\Http\Controllers\Admin\Hrm\EmployeeAdvanceController::class)->except(['show']);
    Route::post('employee-advance/data', [App\Http\Controllers\Admin\Hrm\EmployeeAdvanceController::class, 'getData'])->name('employee-advance-data');
    Route::post('employee-advance/{employee_advance_id}/decide', [App\Http\Controllers\Admin\Hrm\EmployeeAdvanceController::class, 'decide'])->name('employee-advance.decide');
    Route::get('employee-advance/import/sample', [App\Http\Controllers\Admin\Hrm\EmployeeAdvanceController::class, 'importSample'])->name('employee-advance-import-sample');
    Route::post('employee-advance/import/preview', [App\Http\Controllers\Admin\Hrm\EmployeeAdvanceController::class, 'importPreview'])->name('employee-advance-import-preview');
    Route::post('employee-advance/import/confirm', [App\Http\Controllers\Admin\Hrm\EmployeeAdvanceController::class, 'importConfirm'])->name('employee-advance-import-confirm');
    Route::get('employee-advance/export', [App\Http\Controllers\Admin\Hrm\EmployeeAdvanceController::class, 'export'])->name('employee-advance-export');
    //employee deductions
    Route::resource('employee-deduction', App\Http\Controllers\Admin\Hrm\EmployeeDeductionController::class);
    Route::post('employee-deduction/data', [App\Http\Controllers\Admin\Hrm\EmployeeDeductionController::class, 'getData'])->name('employee-deduction-data');
    //employee ledger
    Route::get('employee-ledger', [App\Http\Controllers\Admin\Hrm\EmployeeLedgerController::class, 'index'])->name('employee-ledger.index');
    Route::post('employee-ledger/data', [App\Http\Controllers\Admin\Hrm\EmployeeLedgerController::class, 'getData'])->name('employee-ledger-data');
    }); // end module:hrm

    Route::group(['middleware' => ['module:payroll']], function () {
    //payroll
    Route::get('payroll', [App\Http\Controllers\Admin\Hrm\PayrollController::class, 'index'])->name('payroll.index');
    Route::post('payroll/data', [App\Http\Controllers\Admin\Hrm\PayrollController::class, 'getData'])->name('payroll-data');
    Route::get('payroll/create', [App\Http\Controllers\Admin\Hrm\PayrollController::class, 'create'])->name('payroll.create');
    Route::post('payroll', [App\Http\Controllers\Admin\Hrm\PayrollController::class, 'store'])->name('payroll.store');
    Route::get('payroll/{payroll_run_id}', [App\Http\Controllers\Admin\Hrm\PayrollController::class, 'show'])->name('payroll.show');
    Route::post('payroll/{payroll_run_id}/finalize', [App\Http\Controllers\Admin\Hrm\PayrollController::class, 'finalize'])->name('payroll.finalize');
    Route::post('payroll/{payroll_run_id}/pay', [App\Http\Controllers\Admin\Hrm\PayrollController::class, 'pay'])->name('payroll.pay');
    Route::post('payroll/{payroll_run_id}/reopen', [App\Http\Controllers\Admin\Hrm\PayrollController::class, 'reopen'])->name('payroll.reopen');
    //payslips
    Route::get('payslip/{payslip_id}', [App\Http\Controllers\Admin\Hrm\PayslipController::class, 'show'])->name('payslip.show');
    Route::get('payslip/{payslip_id}/pdf', [App\Http\Controllers\Admin\Hrm\PayslipController::class, 'pdf'])->name('payslip.pdf');
    }); // end module:payroll

    Route::group(['middleware' => ['module:hrm']], function () {
    //resignation / termination + clearance
    Route::get('employee-exit', [App\Http\Controllers\Admin\Hrm\EmployeeExitController::class, 'index'])->name('employee-exit.index');
    Route::post('employee-exit/data', [App\Http\Controllers\Admin\Hrm\EmployeeExitController::class, 'getData'])->name('employee-exit-data');
    Route::get('employee-exit/create', [App\Http\Controllers\Admin\Hrm\EmployeeExitController::class, 'create'])->name('employee-exit.create');
    Route::post('employee-exit', [App\Http\Controllers\Admin\Hrm\EmployeeExitController::class, 'store'])->name('employee-exit.store');
    Route::get('employee-exit/{employee_exit_id}', [App\Http\Controllers\Admin\Hrm\EmployeeExitController::class, 'show'])->name('employee-exit.show');
    Route::post('employee-exit/{employee_exit_id}/decide', [App\Http\Controllers\Admin\Hrm\EmployeeExitController::class, 'decide'])->name('employee-exit.decide');
    Route::post('employee-exit/{employee_exit_id}/finalize', [App\Http\Controllers\Admin\Hrm\EmployeeExitController::class, 'finalize'])->name('employee-exit.finalize');
    Route::post('exit-clearance/{exit_clearance_id}/decide', [App\Http\Controllers\Admin\Hrm\EmployeeExitController::class, 'clear'])->name('exit-clearance.decide');
    //assets
    Route::resource('asset', App\Http\Controllers\Admin\Hrm\AssetController::class)->except(['show']);
    Route::post('asset/data', [App\Http\Controllers\Admin\Hrm\AssetController::class, 'getData'])->name('asset-data');
    Route::post('asset/change-status/{id}', [App\Http\Controllers\Admin\Hrm\AssetController::class, 'status']);
    Route::get('asset/import/sample', [App\Http\Controllers\Admin\Hrm\AssetController::class, 'importSample'])->name('asset-import-sample');
    Route::post('asset/import/preview', [App\Http\Controllers\Admin\Hrm\AssetController::class, 'importPreview'])->name('asset-import-preview');
    Route::post('asset/import/confirm', [App\Http\Controllers\Admin\Hrm\AssetController::class, 'importConfirm'])->name('asset-import-confirm');
    Route::get('asset/export', [App\Http\Controllers\Admin\Hrm\AssetController::class, 'export'])->name('asset-export');
    //asset allocation
    Route::get('asset-allocation', [App\Http\Controllers\Admin\Hrm\AssetAllocationController::class, 'index'])->name('asset-allocation.index');
    Route::post('asset-allocation/data', [App\Http\Controllers\Admin\Hrm\AssetAllocationController::class, 'getData'])->name('asset-allocation-data');
    Route::get('asset-allocation/create', [App\Http\Controllers\Admin\Hrm\AssetAllocationController::class, 'create'])->name('asset-allocation.create');
    Route::post('asset-allocation', [App\Http\Controllers\Admin\Hrm\AssetAllocationController::class, 'store'])->name('asset-allocation.store');
    Route::post('asset-allocation/{asset_allocation_id}/return', [App\Http\Controllers\Admin\Hrm\AssetAllocationController::class, 'returnAsset'])->name('asset-allocation.return');
    Route::get('asset-allocation/import/sample', [App\Http\Controllers\Admin\Hrm\AssetAllocationController::class, 'importSample'])->name('asset-allocation-import-sample');
    Route::post('asset-allocation/import/preview', [App\Http\Controllers\Admin\Hrm\AssetAllocationController::class, 'importPreview'])->name('asset-allocation-import-preview');
    Route::post('asset-allocation/import/confirm', [App\Http\Controllers\Admin\Hrm\AssetAllocationController::class, 'importConfirm'])->name('asset-allocation-import-confirm');
    Route::get('asset-allocation/export', [App\Http\Controllers\Admin\Hrm\AssetAllocationController::class, 'export'])->name('asset-allocation-export');

    //////////////////// Employee Self-Service (ESS) ////////////////////
    Route::group(['prefix' => 'ess'], function () {
        Route::get('/', [App\Http\Controllers\Admin\Hrm\Ess\EssDashboardController::class, 'index'])->name('ess.dashboard');

        Route::get('attendance', [App\Http\Controllers\Admin\Hrm\Ess\EssAttendanceController::class, 'index'])->name('ess.attendance.index');
        Route::post('attendance/check-in', [App\Http\Controllers\Admin\Hrm\Ess\EssAttendanceController::class, 'checkIn'])->name('ess.attendance.check-in');
        Route::post('attendance/check-out', [App\Http\Controllers\Admin\Hrm\Ess\EssAttendanceController::class, 'checkOut'])->name('ess.attendance.check-out');

        Route::get('leave', [App\Http\Controllers\Admin\Hrm\Ess\EssLeaveController::class, 'index'])->name('ess.leave.index');
        Route::get('leave/create', [App\Http\Controllers\Admin\Hrm\Ess\EssLeaveController::class, 'create'])->name('ess.leave.create');
        Route::post('leave', [App\Http\Controllers\Admin\Hrm\Ess\EssLeaveController::class, 'store'])->name('ess.leave.store');
        Route::post('leave/{leave_request_id}/cancel', [App\Http\Controllers\Admin\Hrm\Ess\EssLeaveController::class, 'cancel'])->name('ess.leave.cancel');

        Route::get('payslip', [App\Http\Controllers\Admin\Hrm\Ess\EssPayslipController::class, 'index'])->name('ess.payslip.index');
        Route::get('payslip/{payslip_id}', [App\Http\Controllers\Admin\Hrm\Ess\EssPayslipController::class, 'show'])->name('ess.payslip.show');
        Route::get('payslip/{payslip_id}/pdf', [App\Http\Controllers\Admin\Hrm\Ess\EssPayslipController::class, 'pdf'])->name('ess.payslip.pdf');

        Route::get('profile', [App\Http\Controllers\Admin\Hrm\Ess\EssProfileController::class, 'index'])->name('ess.profile.index');

        Route::get('advance', [App\Http\Controllers\Admin\Hrm\Ess\EssAdvanceController::class, 'index'])->name('ess.advance.index');
        Route::get('advance/create', [App\Http\Controllers\Admin\Hrm\Ess\EssAdvanceController::class, 'create'])->name('ess.advance.create');
        Route::post('advance', [App\Http\Controllers\Admin\Hrm\Ess\EssAdvanceController::class, 'store'])->name('ess.advance.store');

        Route::get('exit', [App\Http\Controllers\Admin\Hrm\Ess\EssExitController::class, 'index'])->name('ess.exit.index');
        Route::get('exit/create', [App\Http\Controllers\Admin\Hrm\Ess\EssExitController::class, 'create'])->name('ess.exit.create');
        Route::post('exit', [App\Http\Controllers\Admin\Hrm\Ess\EssExitController::class, 'store'])->name('ess.exit.store');
    });
    }); // end module:hrm (resignation/clearance/asset/ESS)

    ////////////////////// HRM & Payroll Reports ///////////////////////////
    Route::group(['middleware' => ['module:hrm']], function () {
        Route::group(['prefix' => 'reports'], function () {
            foreach ([
                'employee-master-report' => App\Http\Controllers\Admin\Reports\Hrm\Employee\EmployeeMasterReportController::class,
                'employee-directory-report' => App\Http\Controllers\Admin\Reports\Hrm\Employee\EmployeeDirectoryReportController::class,
                'employee-joining-report' => App\Http\Controllers\Admin\Reports\Hrm\Employee\EmployeeJoiningReportController::class,
                'employee-exit-report' => App\Http\Controllers\Admin\Reports\Hrm\Employee\EmployeeExitReportController::class,
                'department-wise-employee-report' => App\Http\Controllers\Admin\Reports\Hrm\Employee\DepartmentWiseEmployeeReportController::class,
                'designation-wise-employee-report' => App\Http\Controllers\Admin\Reports\Hrm\Employee\DesignationWiseEmployeeReportController::class,
                'branch-wise-employee-report' => App\Http\Controllers\Admin\Reports\Hrm\Employee\BranchWiseEmployeeReportController::class,
                'employee-status-report' => App\Http\Controllers\Admin\Reports\Hrm\Employee\EmployeeStatusReportController::class,
                'attendance-summary-report' => App\Http\Controllers\Admin\Reports\Hrm\Attendance\AttendanceSummaryReportController::class,
                'daily-attendance-report' => App\Http\Controllers\Admin\Reports\Hrm\Attendance\DailyAttendanceReportController::class,
                'monthly-attendance-report' => App\Http\Controllers\Admin\Reports\Hrm\Attendance\MonthlyAttendanceReportController::class,
                'attendance-register' => App\Http\Controllers\Admin\Reports\Hrm\Attendance\AttendanceRegisterReportController::class,
                'late-attendance-report' => App\Http\Controllers\Admin\Reports\Hrm\Attendance\LateAttendanceReportController::class,
                'early-checkout-report' => App\Http\Controllers\Admin\Reports\Hrm\Attendance\EarlyCheckoutReportController::class,
                'absent-employees-report' => App\Http\Controllers\Admin\Reports\Hrm\Attendance\AbsentEmployeesReportController::class,
                'missing-checkin-checkout-report' => App\Http\Controllers\Admin\Reports\Hrm\Attendance\MissingCheckinCheckoutReportController::class,
                'overtime-report' => App\Http\Controllers\Admin\Reports\Hrm\Attendance\OvertimeReportController::class,
                'shift-wise-attendance-report' => App\Http\Controllers\Admin\Reports\Hrm\Attendance\ShiftWiseAttendanceReportController::class,
                'shift-assignment-report' => App\Http\Controllers\Admin\Reports\Hrm\Attendance\ShiftAssignmentReportController::class,
                'leave-summary-report' => App\Http\Controllers\Admin\Reports\Hrm\Leave\LeaveSummaryReportController::class,
                'employee-leave-history-report' => App\Http\Controllers\Admin\Reports\Hrm\Leave\EmployeeLeaveHistoryReportController::class,
                'leave-type-wise-report' => App\Http\Controllers\Admin\Reports\Hrm\Leave\LeaveTypeWiseReportController::class,
                'department-wise-leave-report' => App\Http\Controllers\Admin\Reports\Hrm\Leave\DepartmentWiseLeaveReportController::class,
                'pending-leave-approval-report' => App\Http\Controllers\Admin\Reports\Hrm\Leave\PendingLeaveApprovalReportController::class,
                'leave-approval-status-report' => App\Http\Controllers\Admin\Reports\Hrm\Leave\LeaveApprovalStatusReportController::class,
                'leave-balance-report' => App\Http\Controllers\Admin\Reports\Hrm\Leave\LeaveBalanceReportController::class,
                'salary-structure-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\SalaryStructureReportController::class,
                'salary-component-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\SalaryComponentReportController::class,
                'deduction-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\DeductionReportController::class,
                'employee-advance-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\EmployeeAdvanceReportController::class,
                'advance-recovery-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\AdvanceRecoveryReportController::class,
                'employee-ledger-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\EmployeeLedgerReportController::class,
                'resignation-report' => App\Http\Controllers\Admin\Reports\Hrm\Lifecycle\ResignationReportController::class,
                'termination-report' => App\Http\Controllers\Admin\Reports\Hrm\Lifecycle\TerminationReportController::class,
                'employee-clearance-report' => App\Http\Controllers\Admin\Reports\Hrm\Lifecycle\EmployeeClearanceReportController::class,
                'asset-allocation-report' => App\Http\Controllers\Admin\Reports\Hrm\Lifecycle\AssetAllocationReportController::class,
                'employee-asset-return-report' => App\Http\Controllers\Admin\Reports\Hrm\Lifecycle\EmployeeAssetReturnReportController::class,
                'employee-document-report' => App\Http\Controllers\Admin\Reports\Hrm\Lifecycle\EmployeeDocumentReportController::class,
                'employee-lifecycle-report' => App\Http\Controllers\Admin\Reports\Hrm\Lifecycle\EmployeeLifecycleReportController::class,
            ] as $key => $controller) {
                Route::group(['prefix' => $key], function () use ($key, $controller) {
                    Route::get('/', [$controller, 'index']);
                    Route::post('data', [$controller, 'data']);
                    Route::get('print', [$controller, 'print'])->name("reports.{$key}.print");
                    Route::get('pdf', [$controller, 'pdf'])->name("reports.{$key}.pdf");
                    Route::get('export', [$controller, 'export'])->name("reports.{$key}.export");
                    Route::get('export-csv', [$controller, 'exportCsv'])->name("reports.{$key}.export-csv");
                });
            }

            // Standalone widget report - no data/print/pdf/export, doesn't fit the tabular shape above.
            Route::get('hr-dashboard-report', [App\Http\Controllers\Admin\Reports\Hrm\HrDashboardReportController::class, 'index'])
                ->name('reports.hr-dashboard-report.view');
        });
    }); // end module:hrm (HRM reports)

    Route::group(['middleware' => ['module:payroll']], function () {
        Route::group(['prefix' => 'reports'], function () {
            foreach ([
                'payroll-summary-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\PayrollSummaryReportController::class,
                'employee-wise-payroll-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\EmployeeWisePayrollReportController::class,
                'department-wise-payroll-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\DepartmentWisePayrollReportController::class,
                'branch-wise-payroll-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\BranchWisePayrollReportController::class,
                'monthly-payroll-register' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\MonthlyPayrollRegisterController::class,
                'payroll-cost-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\PayrollCostReportController::class,
                'pending-payroll-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\PendingPayrollReportController::class,
                'salary-slip-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\SalarySlipReportController::class,
                'payroll-disbursement-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\PayrollDisbursementReportController::class,
                'attendance-payroll-comparison-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\AttendancePayrollComparisonReportController::class,
                'leave-payroll-impact-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\LeavePayrollImpactReportController::class,
                'employee-cost-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\EmployeeCostReportController::class,
                'department-payroll-cost-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\DepartmentPayrollCostReportController::class,
                'branch-payroll-cost-report' => App\Http\Controllers\Admin\Reports\Hrm\PayrollFinance\BranchPayrollCostReportController::class,
            ] as $key => $controller) {
                Route::group(['prefix' => $key], function () use ($key, $controller) {
                    Route::get('/', [$controller, 'index']);
                    Route::post('data', [$controller, 'data']);
                    Route::get('print', [$controller, 'print'])->name("reports.{$key}.print");
                    Route::get('pdf', [$controller, 'pdf'])->name("reports.{$key}.pdf");
                    Route::get('export', [$controller, 'export'])->name("reports.{$key}.export");
                    Route::get('export-csv', [$controller, 'exportCsv'])->name("reports.{$key}.export-csv");
                });
            }
        });
    }); // end module:payroll (Payroll reports)

    ////////////////////// Inventory ///////////////////////////
    Route::group(['middleware' => ['module:inventory']], function () {
    //warehouse
    Route::resource('warehouse', App\Http\Controllers\Admin\WarehouseController::class)->except(['show']);
    Route::group(['prefix' => 'warehouse'], function () {
        Route::post('data', [App\Http\Controllers\Admin\WarehouseController::class, 'getData'])->name('warehouse-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\WarehouseController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\WarehouseController::class, 'byBusiness'])->name('warehouse-by-business');
        Route::get('by-branch/{branch_id}', [App\Http\Controllers\Admin\WarehouseController::class, 'byBranch'])->name('warehouse-by-branch');
        Route::get('import/sample', [App\Http\Controllers\Admin\WarehouseController::class, 'importSample'])->name('warehouse-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\WarehouseController::class, 'importPreview'])->name('warehouse-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\WarehouseController::class, 'importConfirm'])->name('warehouse-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\WarehouseController::class, 'export'])->name('warehouse-export');
    });

    //brand
    Route::resource('brands', App\Http\Controllers\Admin\BrandController::class)->except(['show']);
    Route::group(['prefix' => 'brands'], function () {
        Route::post('data', [App\Http\Controllers\Admin\BrandController::class, 'getData'])->name('brands-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\BrandController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\BrandController::class, 'byBusiness'])->name('brand-by-business');
        Route::get('import/sample', [App\Http\Controllers\Admin\BrandController::class, 'importSample'])->name('brand-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\BrandController::class, 'importPreview'])->name('brand-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\BrandController::class, 'importConfirm'])->name('brand-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\BrandController::class, 'export'])->name('brand-export');
    });

    //category
    Route::resource('category', App\Http\Controllers\Admin\CategoryController::class)->except(['show']);
    Route::group(['prefix' => 'category'], function () {
        Route::post('data', [App\Http\Controllers\Admin\CategoryController::class, 'getData'])->name('category-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\CategoryController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\CategoryController::class, 'byBusiness'])->name('category-by-business');
        Route::get('import/sample', [App\Http\Controllers\Admin\CategoryController::class, 'importSample'])->name('category-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\CategoryController::class, 'importPreview'])->name('category-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\CategoryController::class, 'importConfirm'])->name('category-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\CategoryController::class, 'export'])->name('category-export');
    });

    //sub-category
    Route::resource('sub-category', App\Http\Controllers\Admin\SubCategoryController::class)->except(['show']);
    Route::group(['prefix' => 'sub-category'], function () {
        Route::post('data', [App\Http\Controllers\Admin\SubCategoryController::class, 'getData'])->name('sub_category-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\SubCategoryController::class, 'status']);
        Route::get('by-category/{category_id}', [App\Http\Controllers\Admin\SubCategoryController::class, 'byCategory'])->name('sub-category-by-category');
        Route::get('import/sample', [App\Http\Controllers\Admin\SubCategoryController::class, 'importSample'])->name('sub-category-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\SubCategoryController::class, 'importPreview'])->name('sub-category-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\SubCategoryController::class, 'importConfirm'])->name('sub-category-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\SubCategoryController::class, 'export'])->name('sub-category-export');
    });

    //website cms
    Route::resource('website-section', App\Http\Controllers\Admin\WebsiteSectionController::class)->except(['show']);
    Route::group(['prefix' => 'website-section'], function () {
        Route::post('data', [App\Http\Controllers\Admin\WebsiteSectionController::class, 'getData'])->name('website-section-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\WebsiteSectionController::class, 'status']);
    });

    Route::resource('website-page', App\Http\Controllers\Admin\WebsitePageController::class)->except(['show']);
    Route::group(['prefix' => 'website-page'], function () {
        Route::post('data', [App\Http\Controllers\Admin\WebsitePageController::class, 'getData'])->name('website-page-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\WebsitePageController::class, 'status']);
    });

    Route::resource('website-faq', App\Http\Controllers\Admin\WebsiteFaqController::class)->except(['show']);
    Route::group(['prefix' => 'website-faq'], function () {
        Route::post('data', [App\Http\Controllers\Admin\WebsiteFaqController::class, 'getData'])->name('website-faq-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\WebsiteFaqController::class, 'status']);
    });

    Route::resource('social-media', App\Http\Controllers\Admin\SocialMediaLinkController::class)->except(['show']);
    Route::group(['prefix' => 'social-media'], function () {
        Route::post('data', [App\Http\Controllers\Admin\SocialMediaLinkController::class, 'getData'])->name('social-media-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\SocialMediaLinkController::class, 'status']);
    });

    Route::resource('website-hero-stat', App\Http\Controllers\Admin\WebsiteHeroStatController::class)->except(['show']);
    Route::group(['prefix' => 'website-hero-stat'], function () {
        Route::post('data', [App\Http\Controllers\Admin\WebsiteHeroStatController::class, 'getData'])->name('website-hero-stat-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\WebsiteHeroStatController::class, 'status']);
    });

    Route::resource('website-benefit', App\Http\Controllers\Admin\WebsiteBenefitController::class)->except(['show']);
    Route::group(['prefix' => 'website-benefit'], function () {
        Route::post('data', [App\Http\Controllers\Admin\WebsiteBenefitController::class, 'getData'])->name('website-benefit-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\WebsiteBenefitController::class, 'status']);
    });

    Route::resource('website-testimonial', App\Http\Controllers\Admin\WebsiteTestimonialController::class)->except(['show']);
    Route::group(['prefix' => 'website-testimonial'], function () {
        Route::post('data', [App\Http\Controllers\Admin\WebsiteTestimonialController::class, 'getData'])->name('website-testimonial-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\WebsiteTestimonialController::class, 'status']);
    });

    Route::group(['prefix' => 'contact-message'], function () {
        Route::get('/', [App\Http\Controllers\Admin\ContactMessageController::class, 'index'])->name('contact-message-index');
        Route::post('data', [App\Http\Controllers\Admin\ContactMessageController::class, 'getData'])->name('contact-message-data');
        Route::get('{id}/show', [App\Http\Controllers\Admin\ContactMessageController::class, 'show']);
        Route::post('{id}/reply', [App\Http\Controllers\Admin\ContactMessageController::class, 'reply']);
        Route::delete('{id}', [App\Http\Controllers\Admin\ContactMessageController::class, 'destroy']);
    });

    Route::group(['prefix' => 'product-review'], function () {
        Route::get('/', [App\Http\Controllers\Admin\ProductReviewController::class, 'index'])->name('product-review-index');
        Route::post('data', [App\Http\Controllers\Admin\ProductReviewController::class, 'getData'])->name('product-review-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\ProductReviewController::class, 'status']);
        Route::delete('{id}', [App\Http\Controllers\Admin\ProductReviewController::class, 'destroy']);
    });

    Route::group(['prefix' => 'newsletter-subscriber'], function () {
        Route::get('/', [App\Http\Controllers\Admin\NewsletterSubscriberController::class, 'index'])->name('newsletter-subscriber-index');
        Route::post('data', [App\Http\Controllers\Admin\NewsletterSubscriberController::class, 'getData'])->name('newsletter-subscriber-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\NewsletterSubscriberController::class, 'status']);
        Route::delete('{id}', [App\Http\Controllers\Admin\NewsletterSubscriberController::class, 'destroy']);
    });

    //unit
    Route::resource('unit', App\Http\Controllers\Admin\UnitController::class);
    Route::group(['prefix' => 'unit'], function () {
        Route::post('data', [App\Http\Controllers\Admin\UnitController::class, 'getData'])->name('unit-data');
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\UnitController::class, 'status']);
    });

    //product
    Route::resource('product', App\Http\Controllers\Admin\ProductController::class)->except(['show']);
    Route::group(['prefix' => 'product'], function () {
        Route::post('data', [App\Http\Controllers\Admin\ProductController::class, 'getData'])->name('product-data');
        Route::post('change-status/{product_id}', [App\Http\Controllers\Admin\ProductController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\ProductController::class, 'byBusiness'])->name('product-by-business');
        Route::get('import/sample', [App\Http\Controllers\Admin\ProductController::class, 'importSample'])->name('product-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\ProductController::class, 'importPreview'])->name('product-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\ProductController::class, 'importConfirm'])->name('product-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\ProductController::class, 'export'])->name('product-export');
        // variations
        Route::get('variation-by-product/{product_id}', [App\Http\Controllers\Admin\ProductController::class, 'byProduct']);
        Route::get('variations/{product_id}', [App\Http\Controllers\Admin\ProductController::class, 'variations'])->name('product-variations');
        Route::post('variation/status/{variation_id}', [App\Http\Controllers\Admin\ProductController::class, 'variationStatus']);
        Route::delete('variation/delete/{variation_id}', [App\Http\Controllers\Admin\ProductController::class, 'variationDestroy']);
        Route::get('variation-price-history/{product_variation_id}', [App\Http\Controllers\Admin\ProductController::class, 'variationPriceHistory']);
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
        Route::delete('{product_variation_stock_id}', [App\Http\Controllers\Admin\ProductVariationStockController::class, 'destroy']);
    });

    // product variation stock transaction
    Route::group(['prefix' => 'product-variation-stock-transaction'], function () {
        Route::get('/', [App\Http\Controllers\Admin\ProductVariationStockTransactionController::class, 'index']);
        Route::post('data', [App\Http\Controllers\Admin\ProductVariationStockTransactionController::class, 'getData']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\ProductVariationStockTransactionController::class, 'byBusiness']);
        Route::get('by-warehouse/{warehouse_id}', [App\Http\Controllers\Admin\ProductVariationStockTransactionController::class, 'byWarehouse']);
        Route::get('by-product/{product_id}', [App\Http\Controllers\Admin\ProductVariationStockTransactionController::class, 'byProduct']);
        Route::get('by-variation/{product_variation_id}', [App\Http\Controllers\Admin\ProductVariationStockTransactionController::class, 'byVariation']);
        Route::delete('{stock_transaction_id}', [App\Http\Controllers\Admin\ProductVariationStockTransactionController::class, 'destroy']);
    });
    }); // end module:inventory (catalog & warehousing)

    Route::group(['middleware' => ['module:accounting']], function () {
    //Account Types
    Route::resource('account-type', App\Http\Controllers\Admin\AccountTypeController::class);
    Route::group(['prefix' => 'account-type'], function () {
        Route::post('data', [App\Http\Controllers\Admin\AccountTypeController::class, 'getData']);
        Route::post('reset', [App\Http\Controllers\Admin\AccountTypeController::class, 'reset']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\AccountTypeController::class, 'byBusiness']);
        Route::get('template', [App\Http\Controllers\Admin\AccountTypeController::class, 'template']);
    });

    // Fixed Asset Categories (accounting PPE — not HRM assets)
    Route::group(['prefix' => 'fixed-asset-category'], function () {
        Route::get('/', [App\Http\Controllers\Admin\FixedAssetCategoryController::class, 'index']);
        Route::post('data', [App\Http\Controllers\Admin\FixedAssetCategoryController::class, 'getData']);
        Route::get('create', [App\Http\Controllers\Admin\FixedAssetCategoryController::class, 'create']);
        Route::post('store', [App\Http\Controllers\Admin\FixedAssetCategoryController::class, 'store']);
        Route::get('edit/{fixed_asset_category_id}', [App\Http\Controllers\Admin\FixedAssetCategoryController::class, 'edit']);
        Route::get('status/{fixed_asset_category_id}', [App\Http\Controllers\Admin\FixedAssetCategoryController::class, 'status']);
        Route::delete('{fixed_asset_category_id}', [App\Http\Controllers\Admin\FixedAssetCategoryController::class, 'destroy']);
    });

    // Fixed Assets (accounting PPE)
    Route::group(['prefix' => 'fixed-asset'], function () {
        Route::get('/', [App\Http\Controllers\Admin\FixedAssetController::class, 'index']);
        Route::post('data', [App\Http\Controllers\Admin\FixedAssetController::class, 'getData']);
        Route::get('create', [App\Http\Controllers\Admin\FixedAssetController::class, 'create']);
        Route::post('store', [App\Http\Controllers\Admin\FixedAssetController::class, 'store']);
        Route::get('edit/{fixed_asset_id}', [App\Http\Controllers\Admin\FixedAssetController::class, 'edit']);
        Route::get('show/{fixed_asset_id}', [App\Http\Controllers\Admin\FixedAssetController::class, 'show']);
        Route::delete('{fixed_asset_id}', [App\Http\Controllers\Admin\FixedAssetController::class, 'destroy']);
        Route::post('{fixed_asset_id}/pause', [App\Http\Controllers\Admin\FixedAssetController::class, 'pause']);
        Route::post('{fixed_asset_id}/resume', [App\Http\Controllers\Admin\FixedAssetController::class, 'resume']);
        Route::post('{fixed_asset_id}/depreciate', [App\Http\Controllers\Admin\FixedAssetController::class, 'depreciate']);
        Route::post('{fixed_asset_id}/transfer', [App\Http\Controllers\Admin\FixedAssetController::class, 'transfer']);
        Route::post('{fixed_asset_id}/adjust', [App\Http\Controllers\Admin\FixedAssetController::class, 'adjust']);
        Route::post('{fixed_asset_id}/dispose', [App\Http\Controllers\Admin\FixedAssetController::class, 'dispose']);
    });

    // Fixed Asset Depreciation (business-scoped CRUD)
    Route::group(['prefix' => 'fixed-asset-depreciation'], function () {
        Route::get('/', [App\Http\Controllers\Admin\FixedAssetDepreciationController::class, 'index']);
        Route::post('data', [App\Http\Controllers\Admin\FixedAssetDepreciationController::class, 'getData']);
        Route::get('create', [App\Http\Controllers\Admin\FixedAssetDepreciationController::class, 'create']);
        Route::post('store', [App\Http\Controllers\Admin\FixedAssetDepreciationController::class, 'store']);
        Route::get('show/{fixed_asset_depreciation_id}', [App\Http\Controllers\Admin\FixedAssetDepreciationController::class, 'show']);
        Route::delete('{fixed_asset_depreciation_id}', [App\Http\Controllers\Admin\FixedAssetDepreciationController::class, 'destroy']);
    });

    //Account Sub Types
    Route::resource('account-sub-type', App\Http\Controllers\Admin\AccountSubTypeController::class);
    Route::group(['prefix' => 'account-sub-type'], function () {
        Route::post('data', [App\Http\Controllers\Admin\AccountSubTypeController::class, 'getData']);
        Route::post('reset', [App\Http\Controllers\Admin\AccountSubTypeController::class, 'reset']);
        Route::get('by-account-type/{account_type_id}', [App\Http\Controllers\Admin\AccountSubTypeController::class, 'byAccountType']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\AccountSubTypeController::class, 'byBusiness']);
    });

    //Expense Categories
    Route::resource('expense-category', App\Http\Controllers\Admin\ExpenseCategoryController::class)->except(['show']);
    Route::group(['prefix' => 'expense-category'], function () {
        Route::post('data', [App\Http\Controllers\Admin\ExpenseCategoryController::class, 'getData']);
        Route::post('reset', [App\Http\Controllers\Admin\ExpenseCategoryController::class, 'reset']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\ExpenseCategoryController::class, 'byBusiness']);
        Route::get('import/sample', [App\Http\Controllers\Admin\ExpenseCategoryController::class, 'importSample'])->name('expense-category-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\ExpenseCategoryController::class, 'importPreview'])->name('expense-category-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\ExpenseCategoryController::class, 'importConfirm'])->name('expense-category-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\ExpenseCategoryController::class, 'export'])->name('expense-category-export');
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
        Route::post('change-status', [App\Http\Controllers\Admin\JournalEntryController::class, 'status'])->name('journal-entry.change-status');
        Route::get('{journal_entry_id}/print', [App\Http\Controllers\Admin\JournalEntryController::class, 'print'])->name('journal-entry.print');
        Route::get('import/sample', [App\Http\Controllers\Admin\JournalEntryController::class, 'importSample'])->name('journal-entry-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\JournalEntryController::class, 'importPreview'])->name('journal-entry-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\JournalEntryController::class, 'importConfirm'])->name('journal-entry-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\JournalEntryController::class, 'export'])->name('journal-entry-export');
    });
    Route::resource('journal-entry', App\Http\Controllers\Admin\JournalEntryController::class)->except(['show']);

    // Bank Reconciliation
    Route::group(['prefix' => 'bank-reconciliation'], function () {
        Route::post('data', [App\Http\Controllers\Admin\BankReconciliationController::class, 'getData'])->name('bank-reconciliation-data');
        Route::get('import/sample', [App\Http\Controllers\Admin\BankReconciliationController::class, 'importSample'])->name('bank-reconciliation.import-sample');
        Route::post('{bank_reconciliation_id}/import', [App\Http\Controllers\Admin\BankReconciliationController::class, 'import'])->name('bank-reconciliation.import');
        Route::get('{bank_reconciliation_id}/balances', [App\Http\Controllers\Admin\BankReconciliationController::class, 'balances'])->name('bank-reconciliation.balances');
        Route::post('{bank_reconciliation_id}/statement-line', [App\Http\Controllers\Admin\BankReconciliationController::class, 'storeStatementLine'])->name('bank-reconciliation.statement-line.store');
        Route::delete('{bank_reconciliation_id}/statement-line/{bank_statement_line_id}', [App\Http\Controllers\Admin\BankReconciliationController::class, 'destroyStatementLine'])->name('bank-reconciliation.statement-line.destroy');
        Route::post('{bank_reconciliation_id}/match', [App\Http\Controllers\Admin\BankReconciliationController::class, 'match'])->name('bank-reconciliation.match');
        Route::post('{bank_reconciliation_id}/unmatch', [App\Http\Controllers\Admin\BankReconciliationController::class, 'unmatch'])->name('bank-reconciliation.unmatch');
        Route::post('{bank_reconciliation_id}/ignore', [App\Http\Controllers\Admin\BankReconciliationController::class, 'ignore'])->name('bank-reconciliation.ignore');
        Route::post('{bank_reconciliation_id}/unignore', [App\Http\Controllers\Admin\BankReconciliationController::class, 'unignore'])->name('bank-reconciliation.unignore');
        Route::get('{bank_reconciliation_id}/suggest-matches', [App\Http\Controllers\Admin\BankReconciliationController::class, 'suggestMatches'])->name('bank-reconciliation.suggest-matches');
        Route::post('{bank_reconciliation_id}/complete', [App\Http\Controllers\Admin\BankReconciliationController::class, 'complete'])->name('bank-reconciliation.complete');
        Route::post('{bank_reconciliation_id}/reopen', [App\Http\Controllers\Admin\BankReconciliationController::class, 'reopen'])->name('bank-reconciliation.reopen');
        Route::get('{bank_reconciliation_id}/print', [App\Http\Controllers\Admin\BankReconciliationController::class, 'print'])->name('bank-reconciliation.print');
        Route::get('{bank_reconciliation_id}/pdf', [App\Http\Controllers\Admin\BankReconciliationController::class, 'pdf'])->name('bank-reconciliation.pdf');
    });
    Route::resource('bank-reconciliation', App\Http\Controllers\Admin\BankReconciliationController::class)
        ->except(['edit'])
        ->parameters(['bank-reconciliation' => 'bank_reconciliation_id']);

    //recurring transactions
    Route::resource('recurring-transaction', App\Http\Controllers\Admin\RecurringTransactionController::class)->except(['show']);
    Route::group(['prefix' => 'recurring-transaction'], function () {
        Route::post('data', [App\Http\Controllers\Admin\RecurringTransactionController::class, 'getData']);
        Route::post('preview-next-run', [App\Http\Controllers\Admin\RecurringTransactionController::class, 'previewNextRun'])->name('recurring-transaction.preview-next-run');
        Route::post('pause/{recurring_transaction_id}', [App\Http\Controllers\Admin\RecurringTransactionController::class, 'pause'])->name('recurring-transaction.pause');
        Route::post('resume/{recurring_transaction_id}', [App\Http\Controllers\Admin\RecurringTransactionController::class, 'resume'])->name('recurring-transaction.resume');
        Route::post('cancel/{recurring_transaction_id}', [App\Http\Controllers\Admin\RecurringTransactionController::class, 'cancel'])->name('recurring-transaction.cancel');
        Route::post('run-now/{recurring_transaction_id}', [App\Http\Controllers\Admin\RecurringTransactionController::class, 'runNow'])->name('recurring-transaction.run-now');
        Route::get('{recurring_transaction_id}/history', [App\Http\Controllers\Admin\RecurringTransactionController::class, 'history'])->name('recurring-transaction.history');
        Route::post('{recurring_transaction_id}/history/data', [App\Http\Controllers\Admin\RecurringTransactionController::class, 'historyData'])->name('recurring-transaction.history-data');
        Route::get('import/sample', [App\Http\Controllers\Admin\RecurringTransactionController::class, 'importSample'])->name('recurring-transaction-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\RecurringTransactionController::class, 'importPreview'])->name('recurring-transaction-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\RecurringTransactionController::class, 'importConfirm'])->name('recurring-transaction-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\RecurringTransactionController::class, 'export'])->name('recurring-transaction-export');
    });
    }); // end module:accounting (chart of accounts, journals, recurring)

    Route::group(['middleware' => ['module:accounting']], function () {
    // Fiscal Years (Advanced Accounting Mode)
    Route::resource('fiscal-year', App\Http\Controllers\Admin\Accounting\FiscalYearController::class)->except(['show']);
    Route::post('fiscal-year/data', [App\Http\Controllers\Admin\Accounting\FiscalYearController::class, 'getData'])->name('fiscal-year-data');

    // Accounting Periods (Advanced Accounting Mode)
    Route::group(['prefix' => 'accounting-period'], function () {
        Route::get('/', [App\Http\Controllers\Admin\Accounting\AccountingPeriodController::class, 'index'])->name('accounting-period.index');
        Route::post('data', [App\Http\Controllers\Admin\Accounting\AccountingPeriodController::class, 'getData'])->name('accounting-period-data');
        Route::get('{accounting_period_id}/issues', [App\Http\Controllers\Admin\Accounting\AccountingPeriodController::class, 'issues'])->name('accounting-period.issues');
        Route::post('{accounting_period_id}/open', [App\Http\Controllers\Admin\Accounting\AccountingPeriodController::class, 'open'])->name('accounting-period.open');
        Route::post('{accounting_period_id}/close', [App\Http\Controllers\Admin\Accounting\AccountingPeriodController::class, 'close'])->name('accounting-period.close');
        Route::post('{accounting_period_id}/reopen', [App\Http\Controllers\Admin\Accounting\AccountingPeriodController::class, 'reopen'])->name('accounting-period.reopen');
    });

    // Period Closing Rules (Advanced Accounting Mode)
    Route::get('period-closing-rule', [App\Http\Controllers\Admin\Accounting\PeriodClosingRuleController::class, 'edit'])->name('period-closing-rule.edit');
    Route::post('period-closing-rule', [App\Http\Controllers\Admin\Accounting\PeriodClosingRuleController::class, 'update'])->name('period-closing-rule.update');

    // Budgets (Advanced Accounting Mode)
    Route::resource('budget', App\Http\Controllers\Admin\Accounting\BudgetController::class)->except(['show']);
    Route::group(['prefix' => 'budget'], function () {
        Route::post('data', [App\Http\Controllers\Admin\Accounting\BudgetController::class, 'getData'])->name('budget-data');
        Route::post('{budget_id}/generate', [App\Http\Controllers\Admin\Accounting\BudgetController::class, 'generate'])->name('budget.generate');
        Route::post('{budget_id}/line', [App\Http\Controllers\Admin\Accounting\BudgetController::class, 'saveLine'])->name('budget.save-line');
    });
    }); // end module:accounting (fiscal years, accounting periods, closing rules, budgets)

    Route::group(['middleware' => ['module:inventory']], function () {
    //supplier
    Route::resource('supplier', App\Http\Controllers\Admin\SupplierController::class)->except(['show']);
    Route::group(['prefix' => 'supplier'], function () {
        Route::get('{supplier_id}/show', [App\Http\Controllers\Admin\SupplierController::class, 'show'])->name('supplier.show');
        Route::post('data', [App\Http\Controllers\Admin\SupplierController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\SupplierController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\SupplierController::class, 'byBusiness']);
        Route::get('import/sample', [App\Http\Controllers\Admin\SupplierController::class, 'importSample'])->name('supplier-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\SupplierController::class, 'importPreview'])->name('supplier-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\SupplierController::class, 'importConfirm'])->name('supplier-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\SupplierController::class, 'export'])->name('supplier-export');
    });
    }); // end module:inventory (supplier master)

    ////////////////////// Orders (centralized) ///////////////////////////
    Route::group(['middleware' => ['module:pos']], function () {
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
    Route::resource('discount', App\Http\Controllers\Admin\DiscountController::class)->except(['show']);
    Route::group(['prefix' => 'discount'], function () {
        Route::post('data', [App\Http\Controllers\Admin\DiscountController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\DiscountController::class, 'status']);
        Route::get('import/sample', [App\Http\Controllers\Admin\DiscountController::class, 'importSample'])->name('discount-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\DiscountController::class, 'importPreview'])->name('discount-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\DiscountController::class, 'importConfirm'])->name('discount-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\DiscountController::class, 'export'])->name('discount-export');
    });
    //sale type (managed inline from POS Settings, no dedicated index page)
    Route::group(['prefix' => 'sale-type'], function () {
        Route::post('data', [App\Http\Controllers\Admin\SaleTypeController::class, 'getData']);
        Route::get('list', [App\Http\Controllers\Admin\SaleTypeController::class, 'list']);
        Route::post('/', [App\Http\Controllers\Admin\SaleTypeController::class, 'store']);
        Route::get('{sale_type_id}/edit', [App\Http\Controllers\Admin\SaleTypeController::class, 'edit']);
        Route::post('change-status/{sale_type_id}', [App\Http\Controllers\Admin\SaleTypeController::class, 'status']);
        Route::delete('{sale_type_id}', [App\Http\Controllers\Admin\SaleTypeController::class, 'destroy']);
    });

    }); // end module:pos (order-type/payment-method/order-source/sale-type/discount)

    ////////////////////// Payment Gateways (Website & Mobile App only - never POS) ///////////////////////////
    Route::group(['middleware' => ['module:payment-gateway']], function () {
    Route::resource('payment-gateway', App\Http\Controllers\Admin\PaymentGatewayController::class)->except(['show']);
    Route::group(['prefix' => 'payment-gateway'], function () {
        Route::post('data', [App\Http\Controllers\Admin\PaymentGatewayController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\PaymentGatewayController::class, 'status']);
        Route::post('test-connection/{id}', [App\Http\Controllers\Admin\PaymentGatewayController::class, 'testConnection']);
    });

    Route::resource('payment-transaction', App\Http\Controllers\Admin\PaymentTransactionController::class)->only(['index', 'show']);
    Route::group(['prefix' => 'payment-transaction'], function () {
        Route::post('data', [App\Http\Controllers\Admin\PaymentTransactionController::class, 'getData']);
        Route::post('refund/{id}', [App\Http\Controllers\Admin\PaymentTransactionController::class, 'refund']);
    });
    }); // end module:payment-gateway

    Route::group(['middleware' => ['module:accounting']], function () {
    //voucher
    Route::resource('voucher', App\Http\Controllers\Admin\VoucherController::class)->except(['show']);
    Route::group(['prefix' => 'voucher'], function () {
        Route::post('data', [App\Http\Controllers\Admin\VoucherController::class, 'getData']);
        Route::get('{voucher_id}/redemptions', [App\Http\Controllers\Admin\VoucherController::class, 'redemptions']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\VoucherController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\VoucherController::class, 'byBusiness'])->name('voucher-by-business');
        Route::get('import/sample', [App\Http\Controllers\Admin\VoucherController::class, 'importSample'])->name('voucher-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\VoucherController::class, 'importPreview'])->name('voucher-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\VoucherController::class, 'importConfirm'])->name('voucher-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\VoucherController::class, 'export'])->name('voucher-export');
    });
    }); // end module:accounting (voucher)

    ////////////////////// POS (operational interface only) ///////////////////////////
    Route::group(['middleware' => ['module:pos']], function () {
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
            Route::get('summary/{pos_register_session_id}/print', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'printSummary'])->name('pos-register-session.summary.print');
            Route::post('cash-movement', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'addCashMovement']);
            Route::post('void', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'void']);
            Route::get('current', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'current']);
            Route::get('my-history', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'myHistory']);
        });
        Route::get('pos-register-session', [App\Http\Controllers\Admin\PosRegisterSessionController::class, 'index']);
    });
    }); // end module:pos (register & session)

    //order (centralized - shared by POS, Website, Mobile App, API)
    Route::group(['middleware' => ['module:pos', 'permission:pos.access']], function () {
        // Registered before the resource route below so these literal paths
        // (e.g. GET order/search-products) are matched before the
        // resource's GET order/{order} (show) wildcard would otherwise
        // swallow them.
        Route::group(['prefix' => 'order'], function () {
            Route::post('data', [App\Http\Controllers\Admin\OrderController::class, 'getData']);
            Route::post('history-summary', [App\Http\Controllers\Admin\OrderController::class, 'historySummary']);
            Route::get('history-summary/print', [App\Http\Controllers\Admin\OrderController::class, 'historySummaryPrint'])->name('order.history-summary.print');
            Route::post('hold', [App\Http\Controllers\Admin\OrderController::class, 'hold'])->middleware('permission:order.hold');
            Route::post('resume', [App\Http\Controllers\Admin\OrderController::class, 'resume'])->middleware('permission:order.hold');
            Route::post('reopen', [App\Http\Controllers\Admin\OrderController::class, 'reopen'])->middleware('permission:order.reopen');
            Route::post('cancel', [App\Http\Controllers\Admin\OrderController::class, 'cancel'])->middleware('permission:order.cancel');
            Route::post('complete', [App\Http\Controllers\Admin\OrderController::class, 'complete'])->middleware('permission:order.complete');
            Route::post('correct', [App\Http\Controllers\Admin\OrderController::class, 'correct'])->middleware('permission:order.correct');
            Route::post('credit-info', [App\Http\Controllers\Admin\OrderController::class, 'updateCreditInfo'])->middleware('permission:order.payment.credit');
            Route::post('void', [App\Http\Controllers\Admin\OrderController::class, 'void'])->middleware('permission:order.void');
            Route::post('change-status', [App\Http\Controllers\Admin\OrderController::class, 'changeStatus'])->middleware('permission:order.complete|order.cancel|order.void');
            Route::post('confirm-payment', [App\Http\Controllers\Admin\OrderController::class, 'confirmPayment'])->middleware('permission:order.complete');
            Route::get('search-products', [App\Http\Controllers\Admin\OrderController::class, 'searchProducts']);
            Route::get('search-vouchers', [App\Http\Controllers\Admin\OrderController::class, 'searchVouchers']);
            Route::get('available-serials', [App\Http\Controllers\Admin\OrderController::class, 'availableSerials']);
            Route::get('eligible-vouchers', [App\Http\Controllers\Admin\OrderController::class, 'eligibleVouchers']);
            Route::post('preview-voucher', [App\Http\Controllers\Admin\OrderController::class, 'previewVoucher']);
            Route::get('products-by-category', [App\Http\Controllers\Admin\OrderController::class, 'productsByCategory']);
            Route::post('resolve-prices', [App\Http\Controllers\Admin\OrderController::class, 'resolvePrices']);
            Route::get('filter-options/{business_id}', [App\Http\Controllers\Admin\OrderController::class, 'filterOptions']);
            Route::get('details/{order_id}', [App\Http\Controllers\Admin\OrderController::class, 'details']);
            Route::get('{order_id}/print', [App\Http\Controllers\Admin\OrderController::class, 'print'])->name('order.print');
            Route::get('{order_id}/thermal-print', [App\Http\Controllers\Admin\OrderController::class, 'thermalPrint'])->name('order.thermal-print');
            Route::get('export', [App\Http\Controllers\Admin\OrderController::class, 'export'])->name('order-export');
        });
        Route::resource('order', App\Http\Controllers\Admin\OrderController::class)->except(['create', 'edit']);
    });

    //order return (sale return)
    Route::group(['middleware' => ['module:pos']], function () {
        Route::resource('order-return', App\Http\Controllers\Admin\OrderReturnController::class);
        Route::group(['prefix' => 'order-return'], function () {
            Route::post('data', [App\Http\Controllers\Admin\OrderReturnController::class, 'getData']);
            Route::post('change-status', [App\Http\Controllers\Admin\OrderReturnController::class, 'status']);
            Route::get('details/{order_return_id}', [App\Http\Controllers\Admin\OrderReturnController::class, 'details']);
            Route::get('source-lines/{order_id}', [App\Http\Controllers\Admin\OrderReturnController::class, 'sourceLines']);
            Route::get('sold-serials/{order_detail_id}', [App\Http\Controllers\Admin\OrderReturnController::class, 'soldSerials']);
            Route::get('{order_return_id}/print', [App\Http\Controllers\Admin\OrderReturnController::class, 'print'])->name('order-return.print');
        });
    }); // end module:pos (order return)

    //customer payment (settles credit orders - paired with Orders/POS, mirrors
    //how supplier-payment sits next to Purchases under module:inventory)
    Route::group(['middleware' => ['module:pos']], function () {
        Route::resource('customer-payment', App\Http\Controllers\Admin\CustomerPaymentController::class)->except(['show', 'update']);
        Route::group(['prefix' => 'customer-payment'], function () {
            Route::post('data', [App\Http\Controllers\Admin\CustomerPaymentController::class, 'getData']);
            Route::post('receive', [App\Http\Controllers\Admin\CustomerPaymentController::class, 'quickReceive']);
            Route::post('change-status', [App\Http\Controllers\Admin\CustomerPaymentController::class, 'status']);
            Route::get('details/{customer_payment_id}', [App\Http\Controllers\Admin\CustomerPaymentController::class, 'details']);
            Route::get('ledger/{user_id}', [App\Http\Controllers\Admin\CustomerPaymentController::class, 'customerLedger']);
            Route::get('orders-by-customer/{user_id}', [App\Http\Controllers\Admin\CustomerPaymentController::class, 'ordersByCustomer']);
            Route::get('service-sales-by-customer/{user_id}', [App\Http\Controllers\Admin\CustomerPaymentController::class, 'serviceSalesByCustomer']);
            Route::get('{customer_payment_id}/print', [App\Http\Controllers\Admin\CustomerPaymentController::class, 'print'])->name('customer-payment.print');
            Route::get('import/sample', [App\Http\Controllers\Admin\CustomerPaymentController::class, 'importSample'])->name('customer-payment-import-sample');
            Route::post('import/preview', [App\Http\Controllers\Admin\CustomerPaymentController::class, 'importPreview'])->name('customer-payment-import-preview');
            Route::post('import/confirm', [App\Http\Controllers\Admin\CustomerPaymentController::class, 'importConfirm'])->name('customer-payment-import-confirm');
            Route::get('export', [App\Http\Controllers\Admin\CustomerPaymentController::class, 'export'])->name('customer-payment-export');
        });
    }); // end module:pos (customer payment)

    //customer reports (ledger/aging/payment-history) - mirrors supplier
    //reports' shape, but scoped to module:pos since these are Customer/POS-
    //side reports, same deviation rationale as customer-payment above
    Route::group(['middleware' => ['module:pos']], function () {
        Route::group(['prefix' => 'reports'], function () {
            Route::group(['prefix' => 'customer-ledger'], function () {
                Route::get('/', [App\Http\Controllers\Admin\Reports\CustomerLedgerReportController::class, 'index']);
                Route::post('data', [App\Http\Controllers\Admin\Reports\CustomerLedgerReportController::class, 'data']);
                Route::get('print', [App\Http\Controllers\Admin\Reports\CustomerLedgerReportController::class, 'print'])->name('reports.customer-ledger.print');
                Route::get('pdf', [App\Http\Controllers\Admin\Reports\CustomerLedgerReportController::class, 'pdf'])->name('reports.customer-ledger.pdf');
                Route::get('export', [App\Http\Controllers\Admin\Reports\CustomerLedgerReportController::class, 'export'])->name('reports.customer-ledger.export');
                Route::get('export-csv', [App\Http\Controllers\Admin\Reports\CustomerLedgerReportController::class, 'exportCsv'])->name('reports.customer-ledger.export-csv');
            });

            Route::group(['prefix' => 'customer-aging'], function () {
                Route::get('/', [App\Http\Controllers\Admin\Reports\CustomerAgingReportController::class, 'index']);
                Route::post('data', [App\Http\Controllers\Admin\Reports\CustomerAgingReportController::class, 'data']);
                Route::get('print', [App\Http\Controllers\Admin\Reports\CustomerAgingReportController::class, 'print'])->name('reports.customer-aging.print');
                Route::get('pdf', [App\Http\Controllers\Admin\Reports\CustomerAgingReportController::class, 'pdf'])->name('reports.customer-aging.pdf');
                Route::get('export', [App\Http\Controllers\Admin\Reports\CustomerAgingReportController::class, 'export'])->name('reports.customer-aging.export');
                Route::get('export-csv', [App\Http\Controllers\Admin\Reports\CustomerAgingReportController::class, 'exportCsv'])->name('reports.customer-aging.export-csv');
            });

            Route::group(['prefix' => 'customer-payment-history'], function () {
                Route::get('/', [App\Http\Controllers\Admin\Reports\CustomerPaymentHistoryReportController::class, 'index']);
                Route::post('data', [App\Http\Controllers\Admin\Reports\CustomerPaymentHistoryReportController::class, 'data']);
                Route::get('print', [App\Http\Controllers\Admin\Reports\CustomerPaymentHistoryReportController::class, 'print'])->name('reports.customer-payment-history.print');
                Route::get('pdf', [App\Http\Controllers\Admin\Reports\CustomerPaymentHistoryReportController::class, 'pdf'])->name('reports.customer-payment-history.pdf');
                Route::get('export', [App\Http\Controllers\Admin\Reports\CustomerPaymentHistoryReportController::class, 'export'])->name('reports.customer-payment-history.export');
                Route::get('export-csv', [App\Http\Controllers\Admin\Reports\CustomerPaymentHistoryReportController::class, 'exportCsv'])->name('reports.customer-payment-history.export-csv');
            });

            Route::group(['prefix' => 'customer-loyalty-report'], function () {
                Route::get('/', [App\Http\Controllers\Admin\Reports\LoyaltyHistoryReportController::class, 'index']);
                Route::post('data', [App\Http\Controllers\Admin\Reports\LoyaltyHistoryReportController::class, 'data']);
                Route::get('print', [App\Http\Controllers\Admin\Reports\LoyaltyHistoryReportController::class, 'print'])->name('reports.customer-loyalty-report.print');
                Route::get('pdf', [App\Http\Controllers\Admin\Reports\LoyaltyHistoryReportController::class, 'pdf'])->name('reports.customer-loyalty-report.pdf');
                Route::get('export', [App\Http\Controllers\Admin\Reports\LoyaltyHistoryReportController::class, 'export'])->name('reports.customer-loyalty-report.export');
                Route::get('export-csv', [App\Http\Controllers\Admin\Reports\LoyaltyHistoryReportController::class, 'exportCsv'])->name('reports.customer-loyalty-report.export-csv');
            });

            // Orders reports (POS/order-side aggregates) - same module:pos
            // grouping as customer reports above.
            $orderReportRoutes = [
                'order-detail' => App\Http\Controllers\Admin\Reports\Orders\OrderDetailReportController::class,
                'product-sales' => App\Http\Controllers\Admin\Reports\Orders\ProductSalesReportController::class,
                'variation-sales' => App\Http\Controllers\Admin\Reports\Orders\VariationSalesReportController::class,
                'customer-sales' => App\Http\Controllers\Admin\Reports\Orders\CustomerSalesReportController::class,
                'branch-sales' => App\Http\Controllers\Admin\Reports\Orders\BranchSalesReportController::class,
                'order-source-sales' => App\Http\Controllers\Admin\Reports\Orders\OrderSourceSalesReportController::class,
                'payment-method-sales' => App\Http\Controllers\Admin\Reports\Orders\PaymentMethodSalesReportController::class,
                'order-status-report' => App\Http\Controllers\Admin\Reports\Orders\OrderStatusReportController::class,
                'cancelled-orders' => App\Http\Controllers\Admin\Reports\Orders\CancelledOrdersReportController::class,
                'due-credit-sales' => App\Http\Controllers\Admin\Reports\Orders\DueCreditSalesReportController::class,
                'discount-report' => App\Http\Controllers\Admin\Reports\Orders\DiscountReportController::class,
                'loyalty-report' => App\Http\Controllers\Admin\Reports\Orders\LoyaltyReportController::class,
                'order-tax-report' => App\Http\Controllers\Admin\Reports\Orders\OrderTaxReportController::class,
                'top-selling' => App\Http\Controllers\Admin\Reports\Orders\TopSellingReportController::class,
                'offline-orders-report' => App\Http\Controllers\Admin\Reports\Orders\OfflineOrdersReportController::class,
                'order-correction-report' => App\Http\Controllers\Admin\Reports\Orders\OrderCorrectionReportController::class,
            ];
            foreach ($orderReportRoutes as $prefix => $controller) {
                Route::group(['prefix' => $prefix], function () use ($prefix, $controller) {
                    Route::get('/', [$controller, 'index']);
                    Route::post('data', [$controller, 'data']);
                    Route::get('print', [$controller, 'print'])->name("reports.{$prefix}.print");
                    Route::get('pdf', [$controller, 'pdf'])->name("reports.{$prefix}.pdf");
                    Route::get('export', [$controller, 'export'])->name("reports.{$prefix}.export");
                    Route::get('export-csv', [$controller, 'exportCsv'])->name("reports.{$prefix}.export-csv");
                });
            }
        });
    }); // end module:pos (customer + order reports)

    Route::group(['middleware' => ['module:pos', 'platform:pos']], function () {
    //pos screen
    Route::group(['middleware' => ['permission:pos.access']], function () {
        Route::get('order-history', [App\Http\Controllers\Admin\OrderController::class, 'history'])->name('order.history');
        Route::get('pos-screen', [App\Http\Controllers\Admin\PosScreenController::class, 'index'])->name('pos-screen');
        Route::post('pos-screen/context', [App\Http\Controllers\Admin\PosScreenController::class, 'selectContext'])->name('pos-screen.context');
        Route::get('pos-screen/context-options/{business_id}', [App\Http\Controllers\Admin\PosScreenController::class, 'contextOptions']);
        Route::get('pos-screen/change-context', [App\Http\Controllers\Admin\PosScreenController::class, 'changeContext'])->name('pos-screen.change-context');
        Route::post('pos-screen/quick-customer', [App\Http\Controllers\Admin\PosScreenController::class, 'quickCreateCustomer'])->name('pos-screen.quick-customer');
        Route::post('pos-screen/quick-expense', [App\Http\Controllers\Admin\PosScreenController::class, 'quickCreateExpense'])->name('pos-screen.quick-expense');
        Route::get('pos-screen/notifications/unread-count', [App\Http\Controllers\Admin\PosScreenController::class, 'posNotificationsUnreadCount'])->name('pos-screen.notifications.unread-count');
        Route::get('pos-screen/notifications/latest', [App\Http\Controllers\Admin\PosScreenController::class, 'posNotificationsLatest'])->name('pos-screen.notifications.latest');
    });
    }); // end module:pos (pos screen)

    Route::group(['middleware' => ['module:inventory']], function () {
    //purchase request
    Route::resource('purchase-request', App\Http\Controllers\Admin\PurchaseRequestController::class)->except(['show']);
    Route::group(['prefix' => 'purchase-request'], function () {
        Route::post('data', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'status']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'byBusiness']);
        Route::post('send-quotation',[App\Http\Controllers\Admin\PurchaseRequestController::class,'sendQuotation']);
        Route::get('details/{purchase_request_id}', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'details']);
        Route::get('{purchase_request_id}/print', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'print'])->name('purchase-request.print');
        Route::get('import/sample', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'importSample'])->name('purchase-request-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'importPreview'])->name('purchase-request-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'importConfirm'])->name('purchase-request-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\PurchaseRequestController::class, 'export'])->name('purchase-request-export');
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
        Route::get('available-serials/{purchase_detail_id}', [App\Http\Controllers\Admin\PurchaseReturnController::class, 'availableSerials']);
        Route::get('{purchase_return_id}/print', [App\Http\Controllers\Admin\PurchaseReturnController::class, 'print'])->name('purchase-return.print');
    });

    //opening stock
    Route::resource('opening-stock', App\Http\Controllers\Admin\OpeningStockController::class)->except(['show']);
    Route::group(['prefix' => 'opening-stock'], function () {
        Route::post('data', [App\Http\Controllers\Admin\OpeningStockController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\OpeningStockController::class, 'status']);
        Route::get('details/{opening_stock_id}', [App\Http\Controllers\Admin\OpeningStockController::class, 'details']);
        Route::get('{opening_stock_id}/print', [App\Http\Controllers\Admin\OpeningStockController::class, 'print'])->name('opening-stock.print');
        Route::get('import/sample', [App\Http\Controllers\Admin\OpeningStockController::class, 'importSample'])->name('opening-stock-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\OpeningStockController::class, 'importPreview'])->name('opening-stock-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\OpeningStockController::class, 'importConfirm'])->name('opening-stock-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\OpeningStockController::class, 'export'])->name('opening-stock-export');
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

    //loss reason (configurable Waste/Damage/Expiry reasons)
    Route::resource('loss-reason', App\Http\Controllers\Admin\LossReasonController::class)->except(['show', 'create']);
    Route::group(['prefix' => 'loss-reason'], function () {
        Route::post('data', [App\Http\Controllers\Admin\LossReasonController::class, 'getData']);
        Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\LossReasonController::class, 'byBusiness']);
    });

    //waste / damage / expiry
    Route::resource('waste-damage-expiry', App\Http\Controllers\Admin\WasteDamageExpiryController::class);
    Route::group(['prefix' => 'waste-damage-expiry'], function () {
        Route::post('data', [App\Http\Controllers\Admin\WasteDamageExpiryController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\WasteDamageExpiryController::class, 'status']);
        Route::get('details/{waste_damage_expiry_id}', [App\Http\Controllers\Admin\WasteDamageExpiryController::class, 'details']);
        Route::get('batches/{warehouse_id}/{product_variation_id}', [App\Http\Controllers\Admin\WasteDamageExpiryController::class, 'batches']);
        Route::get('stock/{warehouse_id}/{product_variation_id}', [App\Http\Controllers\Admin\WasteDamageExpiryController::class, 'stock']);
        Route::get('serials/{warehouse_id}/{product_variation_id}', [App\Http\Controllers\Admin\WasteDamageExpiryController::class, 'serials']);
        Route::get('{waste_damage_expiry_id}/print', [App\Http\Controllers\Admin\WasteDamageExpiryController::class, 'print'])->name('waste-damage-expiry.print');
    });

    //serial numbers
    Route::group(['prefix' => 'serial-number'], function () {
        Route::get('/', [App\Http\Controllers\Admin\SerialNumberController::class, 'index']);
        Route::post('data', [App\Http\Controllers\Admin\SerialNumberController::class, 'getData']);
        Route::get('lookup', [App\Http\Controllers\Admin\SerialNumberController::class, 'lookup']);
        Route::get('by-variation/{product_variation_id}', [App\Http\Controllers\Admin\SerialNumberController::class, 'byProduct']);
        Route::post('add-found-unit', [App\Http\Controllers\Admin\SerialNumberController::class, 'addFoundUnit']);
        Route::post('{serial_id}/send-for-repair', [App\Http\Controllers\Admin\SerialNumberController::class, 'sendForRepair']);
        Route::post('{serial_id}/return-from-repair', [App\Http\Controllers\Admin\SerialNumberController::class, 'returnFromRepair']);
        Route::post('{serial_id}/replace', [App\Http\Controllers\Admin\SerialNumberController::class, 'replace']);
        Route::get('{serial_id}', [App\Http\Controllers\Admin\SerialNumberController::class, 'show']);
    });

    //transfer note
    Route::resource('transfer-note', App\Http\Controllers\Admin\TransferNoteController::class)->except(['show']);
    Route::group(['prefix' => 'transfer-note'], function () {
        Route::post('data', [App\Http\Controllers\Admin\TransferNoteController::class, 'getData']);
        Route::post('{transfer_note_id}/send', [App\Http\Controllers\Admin\TransferNoteController::class, 'send'])->name('transfer-note.send');
        Route::post('receive', [App\Http\Controllers\Admin\TransferNoteController::class, 'receive'])->name('transfer-note.receive');
        Route::get('available-serials-for-send/{transfer_note_detail_id}', [App\Http\Controllers\Admin\TransferNoteController::class, 'availableSerialsForSend']);
        Route::get('in-transit-serials/{transfer_note_detail_id}', [App\Http\Controllers\Admin\TransferNoteController::class, 'inTransitSerials']);
        Route::get('details/{transfer_note_id}', [App\Http\Controllers\Admin\TransferNoteController::class, 'details']);
        Route::get('source-stock/{warehouse_id}', [App\Http\Controllers\Admin\TransferNoteController::class, 'sourceStock']);
        Route::get('{transfer_note_id}/print', [App\Http\Controllers\Admin\TransferNoteController::class, 'print'])->name('transfer-note.print');
        Route::get('import/sample', [App\Http\Controllers\Admin\TransferNoteController::class, 'importSample'])->name('transfer-note-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\TransferNoteController::class, 'importPreview'])->name('transfer-note-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\TransferNoteController::class, 'importConfirm'])->name('transfer-note-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\TransferNoteController::class, 'export'])->name('transfer-note-export');
    });

    //supplier payment
    Route::resource('supplier-payment', App\Http\Controllers\Admin\SupplierPaymentController::class)->except(['show']);
    Route::group(['prefix' => 'supplier-payment'], function () {
        Route::post('data', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'status']);
        Route::get('details/{supplier_payment_id}', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'details']);
        Route::get('ledger/{supplier_id}', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'supplierLedger']);
        Route::get('purchases-by-supplier/{supplier_id}', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'purchasesBySupplier']);
        Route::get('service-purchases-by-supplier/{supplier_id}', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'servicePurchasesBySupplier']);
        Route::get('{supplier_payment_id}/print', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'print'])->name('supplier-payment.print');
        Route::get('import/sample', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'importSample'])->name('supplier-payment-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'importPreview'])->name('supplier-payment-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'importConfirm'])->name('supplier-payment-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\SupplierPaymentController::class, 'export'])->name('supplier-payment-export');
    });
    }); // end module:inventory (procurement & stock)

    //manufacturing - recipe/BOM, planning (reservation only), production
    //execution (the only thing that moves stock). Gated on its own umbrella
    //package module, independent of inventory, since a business may want
    //Inventory without Manufacturing.
    Route::group(['middleware' => ['module:manufacturing']], function () {
        Route::group(['prefix' => 'recipe'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Manufacturing\ProductRecipeController::class, 'create']);
            Route::get('for-variation/{product_variation_id}', [App\Http\Controllers\Admin\Manufacturing\ProductRecipeController::class, 'forVariation']);
            Route::post('item', [App\Http\Controllers\Admin\Manufacturing\ProductRecipeController::class, 'storeItem']);
            Route::delete('item/{product_recipe_item_id}', [App\Http\Controllers\Admin\Manufacturing\ProductRecipeController::class, 'destroyItem']);
        });

        Route::group(['prefix' => 'manufacturing-plan'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Manufacturing\ManufacturingPlanController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Manufacturing\ManufacturingPlanController::class, 'getData']);
            Route::get('create', [App\Http\Controllers\Admin\Manufacturing\ManufacturingPlanController::class, 'create']);
            Route::post('store', [App\Http\Controllers\Admin\Manufacturing\ManufacturingPlanController::class, 'store']);
            Route::get('edit/{manufacturing_plan_id}', [App\Http\Controllers\Admin\Manufacturing\ManufacturingPlanController::class, 'edit']);
            Route::get('show/{manufacturing_plan_id}', [App\Http\Controllers\Admin\Manufacturing\ManufacturingPlanController::class, 'show']);
            Route::delete('{manufacturing_plan_id}', [App\Http\Controllers\Admin\Manufacturing\ManufacturingPlanController::class, 'destroy']);
            Route::post('{manufacturing_plan_id}/confirm', [App\Http\Controllers\Admin\Manufacturing\ManufacturingPlanController::class, 'confirm']);
            Route::post('{manufacturing_plan_id}/cancel', [App\Http\Controllers\Admin\Manufacturing\ManufacturingPlanController::class, 'cancel']);
            Route::get('recipe-for-variation/{product_variation_id}', [App\Http\Controllers\Admin\Manufacturing\ManufacturingPlanController::class, 'recipeForVariation']);
            Route::get('eligible', [App\Http\Controllers\Admin\Manufacturing\ManufacturingPlanController::class, 'eligible']);
        });

        Route::group(['prefix' => 'production'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Manufacturing\ProductionController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Manufacturing\ProductionController::class, 'getData']);
            Route::get('create', [App\Http\Controllers\Admin\Manufacturing\ProductionController::class, 'create']);
            Route::post('store', [App\Http\Controllers\Admin\Manufacturing\ProductionController::class, 'store']);
            Route::get('edit/{production_id}', [App\Http\Controllers\Admin\Manufacturing\ProductionController::class, 'edit']);
            Route::get('show/{production_id}', [App\Http\Controllers\Admin\Manufacturing\ProductionController::class, 'show']);
            Route::post('{production_id}/complete', [App\Http\Controllers\Admin\Manufacturing\ProductionController::class, 'complete']);
            Route::post('{production_id}/cancel', [App\Http\Controllers\Admin\Manufacturing\ProductionController::class, 'cancel']);
        });
    }); // end module:manufacturing

    //service management - non-stock purchase/sale (gas cylinders, rentals,
    //installation/delivery charges, etc). Gated on its own package module,
    //independent of inventory/pos, since these transactions never touch stock.
    Route::group(['middleware' => ['module:service-management']], function () {
        //service purchase
        Route::resource('service-purchase', App\Http\Controllers\Admin\ServicePurchaseController::class);
        Route::group(['prefix' => 'service-purchase'], function () {
            Route::post('data', [App\Http\Controllers\Admin\ServicePurchaseController::class, 'getData']);
            Route::post('change-status', [App\Http\Controllers\Admin\ServicePurchaseController::class, 'status']);
            Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\ServicePurchaseController::class, 'byBusiness']);
            Route::get('details/{service_purchase_id}', [App\Http\Controllers\Admin\ServicePurchaseController::class, 'details']);
            Route::get('{service_purchase_id}/print', [App\Http\Controllers\Admin\ServicePurchaseController::class, 'print'])->name('service-purchase.print');
        });

        //service purchase return
        Route::resource('service-purchase-return', App\Http\Controllers\Admin\ServicePurchaseReturnController::class);
        Route::group(['prefix' => 'service-purchase-return'], function () {
            Route::post('data', [App\Http\Controllers\Admin\ServicePurchaseReturnController::class, 'getData']);
            Route::post('change-status', [App\Http\Controllers\Admin\ServicePurchaseReturnController::class, 'status']);
            Route::get('details/{service_purchase_return_id}', [App\Http\Controllers\Admin\ServicePurchaseReturnController::class, 'details']);
            Route::get('source-lines/{service_purchase_id}', [App\Http\Controllers\Admin\ServicePurchaseReturnController::class, 'sourceLines']);
            Route::get('{service_purchase_return_id}/print', [App\Http\Controllers\Admin\ServicePurchaseReturnController::class, 'print'])->name('service-purchase-return.print');
        });

        //service sale
        Route::resource('service-sale', App\Http\Controllers\Admin\ServiceSaleController::class);
        Route::group(['prefix' => 'service-sale'], function () {
            Route::post('data', [App\Http\Controllers\Admin\ServiceSaleController::class, 'getData']);
            Route::post('change-status', [App\Http\Controllers\Admin\ServiceSaleController::class, 'status']);
            Route::get('by-business/{business_id}', [App\Http\Controllers\Admin\ServiceSaleController::class, 'byBusiness']);
            Route::get('details/{service_sale_id}', [App\Http\Controllers\Admin\ServiceSaleController::class, 'details']);
            Route::get('{service_sale_id}/print', [App\Http\Controllers\Admin\ServiceSaleController::class, 'print'])->name('service-sale.print');
        });

        //service sale return
        Route::resource('service-sale-return', App\Http\Controllers\Admin\ServiceSaleReturnController::class);
        Route::group(['prefix' => 'service-sale-return'], function () {
            Route::post('data', [App\Http\Controllers\Admin\ServiceSaleReturnController::class, 'getData']);
            Route::post('change-status', [App\Http\Controllers\Admin\ServiceSaleReturnController::class, 'status']);
            Route::get('details/{service_sale_return_id}', [App\Http\Controllers\Admin\ServiceSaleReturnController::class, 'details']);
            Route::get('source-lines/{service_sale_id}', [App\Http\Controllers\Admin\ServiceSaleReturnController::class, 'sourceLines']);
            Route::get('{service_sale_return_id}/print', [App\Http\Controllers\Admin\ServiceSaleReturnController::class, 'print'])->name('service-sale-return.print');
        });
    }); // end module:service-management

    Route::group(['middleware' => ['module:accounting']], function () {
    //expense detail (POS + Admin, any session/OT) - package-gated by
    // `accounting` per SubscriptionModuleRegistry's `expense` entry (its
    // parent module), not `pos` - it's usable from the POS screen but its
    // subscription toggle lives under Accounting alongside admin-expense.
    Route::resource('expense', App\Http\Controllers\Admin\ExpenseController::class)->except(['show']);
    Route::group(['prefix' => 'expense'], function () {
        Route::post('data', [App\Http\Controllers\Admin\ExpenseController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\ExpenseController::class, 'status']);
        Route::get('details/{expense_id}', [App\Http\Controllers\Admin\ExpenseController::class, 'details']);
        Route::get('import/sample', [App\Http\Controllers\Admin\ExpenseController::class, 'importSample'])->name('expense-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\ExpenseController::class, 'importPreview'])->name('expense-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\ExpenseController::class, 'importConfirm'])->name('expense-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\ExpenseController::class, 'export'])->name('expense-export');
    });
    }); // end module:accounting (expense)

    Route::group(['middleware' => ['module:accounting']], function () {
    //admin expense (business/daily expenses, no POS session or OT)
    Route::resource('admin-expense', App\Http\Controllers\Admin\AdminExpenseController::class)->except(['show']);
    Route::group(['prefix' => 'admin-expense'], function () {
        Route::post('data', [App\Http\Controllers\Admin\AdminExpenseController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\AdminExpenseController::class, 'status']);
        Route::get('import/sample', [App\Http\Controllers\Admin\AdminExpenseController::class, 'importSample'])->name('admin-expense-import-sample');
        Route::post('import/preview', [App\Http\Controllers\Admin\AdminExpenseController::class, 'importPreview'])->name('admin-expense-import-preview');
        Route::post('import/confirm', [App\Http\Controllers\Admin\AdminExpenseController::class, 'importConfirm'])->name('admin-expense-import-confirm');
        Route::get('export', [App\Http\Controllers\Admin\AdminExpenseController::class, 'export'])->name('admin-expense-export');
    });
    }); // end module:accounting (admin expense)

    //audit & security - activity log
    Route::get('activity-log', [App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::post('activity-log/data', [App\Http\Controllers\Admin\ActivityLogController::class, 'getData']);

    //audit & security - login history
    Route::get('login-history', [App\Http\Controllers\Admin\LoginHistoryController::class, 'index'])->name('login-history.index');
    Route::post('login-history/data', [App\Http\Controllers\Admin\LoginHistoryController::class, 'getData']);

    //notifications
    Route::get('notifications', [App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/data', [App\Http\Controllers\Admin\NotificationController::class, 'getData']);
    Route::get('notifications/unread-count', [App\Http\Controllers\Admin\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('notifications/latest', [App\Http\Controllers\Admin\NotificationController::class, 'latest'])->name('notifications.latest');
    Route::post('notifications/{id}/read', [App\Http\Controllers\Admin\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/mark-all-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

    // FCM push: templates & broadcast campaigns (Firebase config lives under Settings → Firebase)
    Route::resource('notification-template', App\Http\Controllers\Admin\NotificationTemplateController::class)->except(['show']);
    Route::group(['prefix' => 'notification-template'], function () {
        Route::post('data', [App\Http\Controllers\Admin\NotificationTemplateController::class, 'getData']);
        Route::post('change-status/{id}', [App\Http\Controllers\Admin\NotificationTemplateController::class, 'status']);
    });

    Route::group(['prefix' => 'broadcast-notification'], function () {
        Route::post('data', [App\Http\Controllers\Admin\BroadcastNotificationController::class, 'getData']);
        Route::get('users-with-tokens', [App\Http\Controllers\Admin\BroadcastNotificationController::class, 'usersWithTokens'])->name('broadcast-notification.users-with-tokens');
        Route::get('templates-by-business', [App\Http\Controllers\Admin\BroadcastNotificationController::class, 'templatesByBusiness'])->name('broadcast-notification.templates-by-business');
        Route::post('{id}/recipients/data', [App\Http\Controllers\Admin\BroadcastNotificationController::class, 'getRecipientData']);
        Route::post('{id}/start', [App\Http\Controllers\Admin\BroadcastNotificationController::class, 'start'])->name('broadcast-notification.start');
        Route::post('{id}/cancel', [App\Http\Controllers\Admin\BroadcastNotificationController::class, 'cancel'])->name('broadcast-notification.cancel');
        Route::post('{id}/resend-failed', [App\Http\Controllers\Admin\BroadcastNotificationController::class, 'resendFailed'])->name('broadcast-notification.resend-failed');
    });
    Route::resource('broadcast-notification', App\Http\Controllers\Admin\BroadcastNotificationController::class)->except(['edit', 'update']);

    //procurement reports
    Route::group(['prefix' => 'reports'], function () {
        Route::group(['middleware' => ['module:inventory']], function () {
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

        Route::group(['prefix' => 'stock-summary'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\StockSummaryReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\StockSummaryReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\StockSummaryReportController::class, 'print'])->name('reports.stock-summary.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\StockSummaryReportController::class, 'pdf'])->name('reports.stock-summary.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\StockSummaryReportController::class, 'export'])->name('reports.stock-summary.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\StockSummaryReportController::class, 'exportCsv'])->name('reports.stock-summary.export-csv');
        });

        Route::group(['prefix' => 'stock-valuation'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\StockValuationReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\StockValuationReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\StockValuationReportController::class, 'print'])->name('reports.stock-valuation.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\StockValuationReportController::class, 'pdf'])->name('reports.stock-valuation.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\StockValuationReportController::class, 'export'])->name('reports.stock-valuation.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\StockValuationReportController::class, 'exportCsv'])->name('reports.stock-valuation.export-csv');
        });

        Route::group(['prefix' => 'stock-aging'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\StockAgingReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\StockAgingReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\StockAgingReportController::class, 'print'])->name('reports.stock-aging.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\StockAgingReportController::class, 'pdf'])->name('reports.stock-aging.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\StockAgingReportController::class, 'export'])->name('reports.stock-aging.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\StockAgingReportController::class, 'exportCsv'])->name('reports.stock-aging.export-csv');
        });

        Route::group(['prefix' => 'stock-transfer-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\StockTransferReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\StockTransferReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\StockTransferReportController::class, 'print'])->name('reports.stock-transfer-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\StockTransferReportController::class, 'pdf'])->name('reports.stock-transfer-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\StockTransferReportController::class, 'export'])->name('reports.stock-transfer-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\StockTransferReportController::class, 'exportCsv'])->name('reports.stock-transfer-report.export-csv');
        });

        Route::group(['prefix' => 'stock-reconciliation'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\StockReconciliationReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\StockReconciliationReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\StockReconciliationReportController::class, 'print'])->name('reports.stock-reconciliation.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\StockReconciliationReportController::class, 'pdf'])->name('reports.stock-reconciliation.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\StockReconciliationReportController::class, 'export'])->name('reports.stock-reconciliation.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\StockReconciliationReportController::class, 'exportCsv'])->name('reports.stock-reconciliation.export-csv');
        });

        Route::group(['prefix' => 'batch-expiry'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\BatchExpiryReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\BatchExpiryReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\BatchExpiryReportController::class, 'print'])->name('reports.batch-expiry.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\BatchExpiryReportController::class, 'pdf'])->name('reports.batch-expiry.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\BatchExpiryReportController::class, 'export'])->name('reports.batch-expiry.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\BatchExpiryReportController::class, 'exportCsv'])->name('reports.batch-expiry.export-csv');
        });

        Route::group(['prefix' => 'stock-loss'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\StockLossReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\StockLossReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\StockLossReportController::class, 'print'])->name('reports.stock-loss.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\StockLossReportController::class, 'pdf'])->name('reports.stock-loss.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\StockLossReportController::class, 'export'])->name('reports.stock-loss.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\StockLossReportController::class, 'exportCsv'])->name('reports.stock-loss.export-csv');
        });

        Route::group(['prefix' => 'waste-damage-expiry'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\WasteDamageExpiryReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\WasteDamageExpiryReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\WasteDamageExpiryReportController::class, 'print'])->name('reports.waste-damage-expiry.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\WasteDamageExpiryReportController::class, 'pdf'])->name('reports.waste-damage-expiry.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\WasteDamageExpiryReportController::class, 'export'])->name('reports.waste-damage-expiry.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\WasteDamageExpiryReportController::class, 'exportCsv'])->name('reports.waste-damage-expiry.export-csv');
        });

        Route::group(['prefix' => 'serial-number-register'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberRegisterReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberRegisterReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberRegisterReportController::class, 'print'])->name('reports.serial-number-register.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberRegisterReportController::class, 'pdf'])->name('reports.serial-number-register.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberRegisterReportController::class, 'export'])->name('reports.serial-number-register.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberRegisterReportController::class, 'exportCsv'])->name('reports.serial-number-register.export-csv');
        });

        Route::group(['prefix' => 'serial-number-available'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberAvailableReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberAvailableReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberAvailableReportController::class, 'print'])->name('reports.serial-number-available.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberAvailableReportController::class, 'pdf'])->name('reports.serial-number-available.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberAvailableReportController::class, 'export'])->name('reports.serial-number-available.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberAvailableReportController::class, 'exportCsv'])->name('reports.serial-number-available.export-csv');
        });

        Route::group(['prefix' => 'serial-number-sold'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberSoldReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberSoldReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberSoldReportController::class, 'print'])->name('reports.serial-number-sold.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberSoldReportController::class, 'pdf'])->name('reports.serial-number-sold.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberSoldReportController::class, 'export'])->name('reports.serial-number-sold.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberSoldReportController::class, 'exportCsv'])->name('reports.serial-number-sold.export-csv');
        });

        Route::group(['prefix' => 'serial-number-movement'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberMovementReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberMovementReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberMovementReportController::class, 'print'])->name('reports.serial-number-movement.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberMovementReportController::class, 'pdf'])->name('reports.serial-number-movement.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberMovementReportController::class, 'export'])->name('reports.serial-number-movement.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberMovementReportController::class, 'exportCsv'])->name('reports.serial-number-movement.export-csv');
        });

        Route::group(['prefix' => 'serial-number-customer'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberCustomerReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberCustomerReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberCustomerReportController::class, 'print'])->name('reports.serial-number-customer.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberCustomerReportController::class, 'pdf'])->name('reports.serial-number-customer.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberCustomerReportController::class, 'export'])->name('reports.serial-number-customer.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\SerialNumberCustomerReportController::class, 'exportCsv'])->name('reports.serial-number-customer.export-csv');
        });
        }); // end module:inventory (procurement reports)

        Route::group(['middleware' => ['module:service-management']], function () {
        //service management reports - non-stock purchase/sale, gated on its
        //own package module same as the Service Management screens themselves.
        Route::group(['prefix' => 'service-sale-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\ServiceSaleReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\ServiceSaleReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\ServiceSaleReportController::class, 'print'])->name('reports.service-sale-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\ServiceSaleReportController::class, 'pdf'])->name('reports.service-sale-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\ServiceSaleReportController::class, 'export'])->name('reports.service-sale-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\ServiceSaleReportController::class, 'exportCsv'])->name('reports.service-sale-report.export-csv');
        });

        Route::group(['prefix' => 'service-purchase-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\ServicePurchaseReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\ServicePurchaseReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\ServicePurchaseReportController::class, 'print'])->name('reports.service-purchase-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\ServicePurchaseReportController::class, 'pdf'])->name('reports.service-purchase-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\ServicePurchaseReportController::class, 'export'])->name('reports.service-purchase-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\ServicePurchaseReportController::class, 'exportCsv'])->name('reports.service-purchase-report.export-csv');
        });

        Route::group(['prefix' => 'service-transaction-summary'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\ServiceTransactionSummaryReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\ServiceTransactionSummaryReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\ServiceTransactionSummaryReportController::class, 'print'])->name('reports.service-transaction-summary.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\ServiceTransactionSummaryReportController::class, 'pdf'])->name('reports.service-transaction-summary.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\ServiceTransactionSummaryReportController::class, 'export'])->name('reports.service-transaction-summary.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\ServiceTransactionSummaryReportController::class, 'exportCsv'])->name('reports.service-transaction-summary.export-csv');
        });

        Route::group(['prefix' => 'service-payment-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\ServicePaymentReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\ServicePaymentReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\ServicePaymentReportController::class, 'print'])->name('reports.service-payment-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\ServicePaymentReportController::class, 'pdf'])->name('reports.service-payment-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\ServicePaymentReportController::class, 'export'])->name('reports.service-payment-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\ServicePaymentReportController::class, 'exportCsv'])->name('reports.service-payment-report.export-csv');
        });
        }); // end module:service-management (service reports)

        Route::group(['middleware' => ['module:accounting']], function () {
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

        Route::group(['prefix' => 'sales-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\SalesReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\SalesReportController::class, 'data']);
            Route::post('reconcile', [App\Http\Controllers\Admin\Reports\SalesReportController::class, 'reconcile']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\SalesReportController::class, 'print'])->name('reports.sales-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\SalesReportController::class, 'pdf'])->name('reports.sales-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\SalesReportController::class, 'export'])->name('reports.sales-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\SalesReportController::class, 'exportCsv'])->name('reports.sales-report.export-csv');
        });

        Route::group(['prefix' => 'voucher-usage-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\VoucherUsageReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\VoucherUsageReportController::class, 'data']);
            Route::post('summary', [App\Http\Controllers\Admin\Reports\VoucherUsageReportController::class, 'summary']);
        });

        Route::group(['prefix' => 'expense-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'print'])->name('reports.expense-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'pdf'])->name('reports.expense-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'export'])->name('reports.expense-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'exportCsv'])->name('reports.expense-report.export-csv');
        });

        Route::group(['prefix' => 'fixed-asset-register'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\FixedAssetRegisterReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\FixedAssetRegisterReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\FixedAssetRegisterReportController::class, 'print'])->name('reports.fixed-asset-register.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\FixedAssetRegisterReportController::class, 'pdf'])->name('reports.fixed-asset-register.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\FixedAssetRegisterReportController::class, 'export'])->name('reports.fixed-asset-register.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\FixedAssetRegisterReportController::class, 'exportCsv'])->name('reports.fixed-asset-register.export-csv');
        });

        Route::group(['prefix' => 'depreciation-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\DepreciationReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\DepreciationReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\DepreciationReportController::class, 'print'])->name('reports.depreciation-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\DepreciationReportController::class, 'pdf'])->name('reports.depreciation-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\DepreciationReportController::class, 'export'])->name('reports.depreciation-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\DepreciationReportController::class, 'exportCsv'])->name('reports.depreciation-report.export-csv');
        });

        Route::group(['prefix' => 'asset-valuation-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\AssetValuationReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\AssetValuationReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\AssetValuationReportController::class, 'print'])->name('reports.asset-valuation-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\AssetValuationReportController::class, 'pdf'])->name('reports.asset-valuation-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\AssetValuationReportController::class, 'export'])->name('reports.asset-valuation-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\AssetValuationReportController::class, 'exportCsv'])->name('reports.asset-valuation-report.export-csv');
        });

        Route::group(['prefix' => 'asset-disposal-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\AssetDisposalReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\AssetDisposalReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\AssetDisposalReportController::class, 'print'])->name('reports.asset-disposal-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\AssetDisposalReportController::class, 'pdf'])->name('reports.asset-disposal-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\AssetDisposalReportController::class, 'export'])->name('reports.asset-disposal-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\AssetDisposalReportController::class, 'exportCsv'])->name('reports.asset-disposal-report.export-csv');
        });

        Route::group(['prefix' => 'expense-detail-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\ExpenseDetailReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\ExpenseDetailReportController::class, 'data']);
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

        // Profit & Loss, Balance Sheet and Cash Flow render as computed statements (no DataTables data() endpoint).
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

        // Cash Flow Statement — direct-method statement (no DataTables data() endpoint).
        Route::group(['prefix' => 'cash-flow'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\CashFlowReportController::class, 'index']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\CashFlowReportController::class, 'print'])->name('reports.cash-flow.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\CashFlowReportController::class, 'pdf'])->name('reports.cash-flow.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\CashFlowReportController::class, 'export'])->name('reports.cash-flow.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\CashFlowReportController::class, 'exportCsv'])->name('reports.cash-flow.export-csv');
        });

        Route::group(['prefix' => 'budget-vs-actual'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\BudgetVarianceReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\BudgetVarianceReportController::class, 'data']);
        });
        }); // end module:accounting (financial reports)

        Route::group(['middleware' => ['module:manufacturing']], function () {
        Route::group(['prefix' => 'manufacturing-plan'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\ManufacturingPlanReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\ManufacturingPlanReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\ManufacturingPlanReportController::class, 'print'])->name('reports.manufacturing-plan-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\ManufacturingPlanReportController::class, 'pdf'])->name('reports.manufacturing-plan-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\ManufacturingPlanReportController::class, 'export'])->name('reports.manufacturing-plan-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\ManufacturingPlanReportController::class, 'exportCsv'])->name('reports.manufacturing-plan-report.export-csv');
        });

        Route::group(['prefix' => 'production'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\ProductionReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\ProductionReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\ProductionReportController::class, 'print'])->name('reports.production-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\ProductionReportController::class, 'pdf'])->name('reports.production-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\ProductionReportController::class, 'export'])->name('reports.production-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\ProductionReportController::class, 'exportCsv'])->name('reports.production-report.export-csv');
        });

        Route::group(['prefix' => 'material-consumption'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\MaterialConsumptionReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\MaterialConsumptionReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\MaterialConsumptionReportController::class, 'print'])->name('reports.material-consumption-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\MaterialConsumptionReportController::class, 'pdf'])->name('reports.material-consumption-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\MaterialConsumptionReportController::class, 'export'])->name('reports.material-consumption-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\MaterialConsumptionReportController::class, 'exportCsv'])->name('reports.material-consumption-report.export-csv');
        });

        Route::group(['prefix' => 'recipe-bom-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\Inventory\RecipeBomReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\Inventory\RecipeBomReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\Inventory\RecipeBomReportController::class, 'print'])->name('reports.recipe-bom-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\Inventory\RecipeBomReportController::class, 'pdf'])->name('reports.recipe-bom-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\Inventory\RecipeBomReportController::class, 'export'])->name('reports.recipe-bom-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\Inventory\RecipeBomReportController::class, 'exportCsv'])->name('reports.recipe-bom-report.export-csv');
        });
        }); // end module:manufacturing (manufacturing reports)
    });

    // Advanced Analytics & BI - package-gated (see SubscriptionModuleRegistry's
    // 'analytics' key), permission-gated via the controller's own constructor
    // middleware (analytics.view / analytics.export). {widget} is validated
    // against a whitelist inside AnalyticsService, never used to dynamically
    // dispatch a method from raw user input.
    Route::group(['middleware' => ['module:analytics']], function () {
        Route::group(['prefix' => 'analytics', 'as' => 'analytics.'], function () {
            Route::get('/', [App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('index');
            Route::get('data/{widget}', [App\Http\Controllers\Admin\AnalyticsController::class, 'data'])->name('data');
            Route::post('table/{widget}', [App\Http\Controllers\Admin\AnalyticsController::class, 'table'])->name('table');
            Route::get('export/{widget}', [App\Http\Controllers\Admin\AnalyticsController::class, 'export'])->name('export');
        });
    }); // end module:analytics

    //Setting
    Route::group(['prefix' => 'setting'], function () {
        Route::get('/', [App\Http\Controllers\Admin\SettingController::class, 'index']);
        Route::post('business', [App\Http\Controllers\Admin\SettingController::class, 'updateBusinessSetting'])->name('business.update');
        Route::post('localization', [App\Http\Controllers\Admin\SettingController::class, 'updateLocalizationSetting'])->name('localization.update');
        Route::post('accounting', [App\Http\Controllers\Admin\SettingController::class, 'updateAccountingSetting'])->name('accounting.update');
        Route::post('inventory', [App\Http\Controllers\Admin\SettingController::class, 'updateInventorySetting'])->name('inventory.update');
        Route::post('customer', [App\Http\Controllers\Admin\SettingController::class, 'updateCustomerSetting'])->name('customer.update');
        Route::post('supplier', [App\Http\Controllers\Admin\SettingController::class, 'updateSupplierSetting'])->name('supplier.update');
        Route::post('email', [App\Http\Controllers\Admin\SettingController::class, 'updateEmailSetting'])->name('email.update');
        Route::post('sms', [App\Http\Controllers\Admin\SettingController::class, 'updateSmsSetting'])->name('sms.update');
        Route::post('whatsapp', [App\Http\Controllers\Admin\SettingController::class, 'updateWhatsappSetting'])->name('whatsapp.update');
        Route::post('firebase', [App\Http\Controllers\Admin\SettingController::class, 'updateFirebaseSetting'])->name('firebase.update');
        Route::post('fbr', [App\Http\Controllers\Admin\SettingController::class, 'updateFbrSetting'])->name('fbr.update');
        Route::post('pos', [App\Http\Controllers\Admin\SettingController::class, 'updatePosSetting'])->name('pos.update');
        Route::post('pra', [App\Http\Controllers\Admin\SettingController::class, 'updatePraSetting'])->name('pra.update');
        Route::post('print', [App\Http\Controllers\Admin\SettingController::class, 'updatePrintSetting'])->name('print.update');
        Route::post('thermal-print', [App\Http\Controllers\Admin\SettingController::class, 'updateThermalPrintSetting'])->name('thermal_print.update');
        Route::post('thermal-print/preview', [App\Http\Controllers\Admin\SettingController::class, 'previewThermalPrintSetting'])->name('thermal_print.preview');
        Route::post('barcode', [App\Http\Controllers\Admin\SettingController::class, 'updateBarcodeSetting'])->name('barcode.update');
        Route::post('theme', [App\Http\Controllers\Admin\SettingController::class, 'updateThemeSetting'])->name('theme.update');
        Route::post('theme/preset', [App\Http\Controllers\Admin\SettingController::class, 'applyThemePreset'])->name('theme.preset');
        Route::post('website-theme', [App\Http\Controllers\Admin\SettingController::class, 'updateWebsiteThemeSetting'])->name('website_theme.update');
        Route::post('website-theme/preset', [App\Http\Controllers\Admin\SettingController::class, 'applyWebsiteThemePreset'])->name('website_theme.preset');
        Route::post('website-settings', [App\Http\Controllers\Admin\SettingController::class, 'updateWebsiteSettings'])->name('website_settings.update');
        Route::post('notification', [App\Http\Controllers\Admin\SettingController::class, 'updateNotificationSetting'])->name('notification.update');
    });

    //Documentation
    Route::group(['prefix' => 'documentation'], function () {
        Route::get('/', [App\Http\Controllers\Admin\DocumentationController::class, 'index'])->name('documentation.index');
        Route::get('business-pdf', [App\Http\Controllers\Admin\DocumentationController::class, 'businessPdf'])->name('documentation.business.pdf');
        Route::get('developer-pdf', [App\Http\Controllers\Admin\DocumentationController::class, 'developerPdf'])->name('documentation.developer.pdf');
        Route::get('business/{section?}', [App\Http\Controllers\Admin\DocumentationController::class, 'business'])->name('documentation.business');
        Route::get('developer/{section?}', [App\Http\Controllers\Admin\DocumentationController::class, 'developer'])->name('documentation.developer');
    });
});
