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

Route::group(['middleware' => ['auth'],['prefix' => 'admin']], function () {
    Route::group(['prefix' => 'admin'], function () {
        Route::resource('permissions', App\Http\Controllers\Admin\PermissionController::class);
        Route::post('permissions-data', [App\Http\Controllers\Admin\PermissionController::class, 'getData'])->name('permissions-data');
        Route::resource('roles', App\Http\Controllers\Admin\RoleController::class);
    });
});
