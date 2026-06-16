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

Route::group(['middleware' => ['auth', 'check.subscription'], 'prefix' => 'admin'], function () {
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
    });
});
