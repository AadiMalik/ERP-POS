<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\NewsletterSubscriberController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\Api\SocialMediaLinkController;
use App\Http\Controllers\Api\WebsiteBenefitController;
use App\Http\Controllers\Api\WebsiteFaqController;
use App\Http\Controllers\Api\WebsiteHeroStatController;
use App\Http\Controllers\Api\WebsiteHomeController;
use App\Http\Controllers\Api\WebsitePageController;
use App\Http\Controllers\Api\WebsiteSectionController;
use App\Http\Controllers\Api\WebsiteSettingController;
use App\Http\Controllers\Api\WebsiteTestimonialController;
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

// Public storefront config - business-wise website theme settings, global
// website settings, and branches, consumed by the Vue frontend before
// render / on demand using its .env-configured business_id.
Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    Route::get('website-theme/{business_id}', [WebsiteThemeController::class, 'show']);
    Route::get('website-settings/{business_id}', [WebsiteSettingController::class, 'show']);
    Route::get('branches/{business_id}', [BranchController::class, 'index']);
    Route::get('categories/{business_id}', [CategoryController::class, 'index']);
    Route::get('brands/{business_id}', [BrandController::class, 'index']);
    Route::get('products/{business_id}', [ProductController::class, 'index']);
    Route::get('products/{business_id}/{slug}', [ProductController::class, 'show']);

    // Website CMS - single optimized homepage payload, plus page-specific
    // read endpoints and public submission endpoints (contact/newsletter).
    Route::get('website-home/{business_id}', [WebsiteHomeController::class, 'show']);
    Route::get('sections/{business_id}/{type}', [WebsiteSectionController::class, 'show']);
    Route::get('faqs/{business_id}', [WebsiteFaqController::class, 'index']);
    Route::get('social-links/{business_id}', [SocialMediaLinkController::class, 'index']);
    Route::get('hero-stats/{business_id}', [WebsiteHeroStatController::class, 'index']);
    Route::get('content-items/{business_id}/{group}', [WebsiteBenefitController::class, 'index']);
    Route::get('testimonials/{business_id}', [WebsiteTestimonialController::class, 'index']);
    Route::get('pages/{business_id}', [WebsitePageController::class, 'index']);
    Route::get('pages/{business_id}/{slug}', [WebsitePageController::class, 'show']);
    Route::get('reviews/{business_id}/{product_id}', [ProductReviewController::class, 'index']);
    Route::post('contact/{business_id}', [ContactMessageController::class, 'store']);
    Route::post('newsletter/subscribe/{business_id}', [NewsletterSubscriberController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::post('reviews/{business_id}', [ProductReviewController::class, 'store']);
});
