<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\WebsiteThemeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Shared email+OTP customer identity/auth API - consumed identically by the
// website and the mobile app (and staff-side customer creation reuses the
// same User/CustomerProfile data, just without going through OTP).
Route::prefix('v1/auth')->middleware('throttle:20,1')->group(function () {
    Route::post('check-email', [AuthController::class, 'checkEmail']);
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('login-password', [AuthController::class, 'loginWithPassword']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('set-password', [AuthController::class, 'setPassword']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// Public storefront config - business-wise website theme settings, consumed
// by the Vue frontend before render using its .env-configured business_id.
Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    Route::get('website-theme/{business_id}', [WebsiteThemeController::class, 'show']);
});
