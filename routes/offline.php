<?php

use App\Http\Controllers\Api\Offline\AuthController;
use App\Http\Controllers\Api\Offline\CustomerController;
use App\Http\Controllers\Api\Offline\DeviceController;
use App\Http\Controllers\Api\Offline\OrderController;
use App\Http\Controllers\Api\Offline\RegisterSessionController;
use App\Http\Controllers\Api\Offline\SettingsController;
use App\Http\Controllers\Api\Offline\SetupController;
use App\Http\Controllers\Api\Offline\StockController;
use App\Http\Controllers\Api\Offline\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Offline Desktop POS API
| Prefix: /api/offline
|--------------------------------------------------------------------------
| Dedicated API surface for the Electron desktop POS client. Controllers live
| under App\Http\Controllers\Api\Offline\ and services under
| App\Services\Concrete\Api\Offline\. Reuses existing Admin services
| (OrderService, PosRegisterSessionService, etc.) wherever possible.
*/

Route::prefix('auth')->middleware('throttle:30,1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('ping', [AuthController::class, 'ping']);
});

Route::prefix('setup')->middleware('throttle:30,1')->group(function () {
    Route::post('validate-business', [SetupController::class, 'validateBusiness']);
    Route::post('bootstrap-business', [SetupController::class, 'bootstrapBusiness']);
    Route::post('register-device', [SetupController::class, 'registerDeviceSetup']);
});

Route::middleware(['auth:sanctum', 'module:pos', 'module:offline-pos', 'platform:offline-pos', 'throttle:60,1'])->group(function () {
    Route::get('setup/location-options', [SetupController::class, 'locationOptions']);
    Route::post('device/register', [DeviceController::class, 'register']);
    Route::get('device/info', [DeviceController::class, 'info']);
});

Route::middleware(['auth:sanctum', 'offline.pos.device', 'module:pos', 'module:offline-pos', 'platform:offline-pos', 'throttle:120,1'])->group(function () {
    Route::get('settings/context', [SettingsController::class, 'context']);

    Route::get('sync/bootstrap', [SyncController::class, 'bootstrap']);
    Route::post('sync/pull', [SyncController::class, 'pull']);
    Route::post('sync/push', [SyncController::class, 'push']);
    Route::get('sync/health', [SyncController::class, 'health']);

    Route::get('stock/levels', [StockController::class, 'levels']);
    Route::post('stock/movements', [StockController::class, 'pushMovements']);

    Route::post('orders', [OrderController::class, 'store']);
    Route::post('orders/complete', [OrderController::class, 'complete']);
    Route::post('orders/hold', [OrderController::class, 'hold']);

    Route::post('customers', [CustomerController::class, 'store']);

    Route::post('register-sessions/open', [RegisterSessionController::class, 'open']);
    Route::post('register-sessions/close', [RegisterSessionController::class, 'close']);
    Route::post('register-sessions/cash-movement', [RegisterSessionController::class, 'cashMovement']);
});
