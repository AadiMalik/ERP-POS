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
    Route::post('permissions-data', [App\Http\Controllers\Admin\PermissionController::class, 'getData'])->name('permissions-data');
    //roles
    Route::resource('roles', App\Http\Controllers\Admin\RoleController::class);
    Route::post('roles-data', [App\Http\Controllers\Admin\RoleController::class, 'getData'])->name('roles-data');
    Route::post('roles-reset', [App\Http\Controllers\Admin\RoleController::class, 'reset'])->name('roles-reset');

    //packages
    Route::resource('packages', App\Http\Controllers\Admin\PackageController::class);
    Route::post('packages-data', [App\Http\Controllers\Admin\PackageController::class, 'getData'])->name('packages-data');

    //business
    Route::resource('business', App\Http\Controllers\Admin\BusinessController::class);
    Route::post('business-data', [App\Http\Controllers\Admin\BusinessController::class, 'getData'])->name('business-data');
});
