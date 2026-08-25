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

    Route::group(['middleware' => ['module:accounting']], function () {
    //voucher
    Route::resource('voucher', App\Http\Controllers\Admin\VoucherController::class)->except(['show']);
    Route::group(['prefix' => 'voucher'], function () {
        Route::post('data', [App\Http\Controllers\Admin\VoucherController::class, 'getData']);
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
            Route::post('credit-info', [App\Http\Controllers\Admin\OrderController::class, 'updateCreditInfo']);
            Route::post('void', [App\Http\Controllers\Admin\OrderController::class, 'void'])->middleware('permission:order.void');
            Route::get('search-products', [App\Http\Controllers\Admin\OrderController::class, 'searchProducts']);
            Route::get('search-vouchers', [App\Http\Controllers\Admin\OrderController::class, 'searchVouchers']);
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
        });
    }); // end module:pos (customer reports)

    Route::group(['middleware' => ['module:pos']], function () {
    //pos screen
    Route::group(['middleware' => ['permission:pos.access']], function () {
        Route::get('order-history', [App\Http\Controllers\Admin\OrderController::class, 'history'])->name('order.history');
        Route::get('pos-screen', [App\Http\Controllers\Admin\PosScreenController::class, 'index'])->name('pos-screen');
        Route::post('pos-screen/context', [App\Http\Controllers\Admin\PosScreenController::class, 'selectContext'])->name('pos-screen.context');
        Route::get('pos-screen/context-options/{business_id}', [App\Http\Controllers\Admin\PosScreenController::class, 'contextOptions']);
        Route::get('pos-screen/change-context', [App\Http\Controllers\Admin\PosScreenController::class, 'changeContext'])->name('pos-screen.change-context');
        Route::post('pos-screen/quick-customer', [App\Http\Controllers\Admin\PosScreenController::class, 'quickCreateCustomer'])->name('pos-screen.quick-customer');
        Route::post('pos-screen/quick-expense', [App\Http\Controllers\Admin\PosScreenController::class, 'quickCreateExpense'])->name('pos-screen.quick-expense');
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

    //transfer note
    Route::resource('transfer-note', App\Http\Controllers\Admin\TransferNoteController::class)->except(['show']);
    Route::group(['prefix' => 'transfer-note'], function () {
        Route::post('data', [App\Http\Controllers\Admin\TransferNoteController::class, 'getData']);
        Route::post('change-status', [App\Http\Controllers\Admin\TransferNoteController::class, 'status']);
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

        Route::group(['prefix' => 'expense-report'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'data']);
            Route::get('print', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'print'])->name('reports.expense-report.print');
            Route::get('pdf', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'pdf'])->name('reports.expense-report.pdf');
            Route::get('export', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'export'])->name('reports.expense-report.export');
            Route::get('export-csv', [App\Http\Controllers\Admin\Reports\ExpenseReportController::class, 'exportCsv'])->name('reports.expense-report.export-csv');
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

        Route::group(['prefix' => 'budget-vs-actual'], function () {
            Route::get('/', [App\Http\Controllers\Admin\Reports\BudgetVarianceReportController::class, 'index']);
            Route::post('data', [App\Http\Controllers\Admin\Reports\BudgetVarianceReportController::class, 'data']);
        });
        }); // end module:accounting (financial reports)
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
