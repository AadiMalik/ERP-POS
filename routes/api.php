<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\CustomerOrderController;
use App\Http\Controllers\Api\NewsletterSubscriberController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\Api\ProfileController;
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
use App\Http\Controllers\Api\WishlistController;
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

// Shared email+OTP customer identity/auth API for the Vue storefront.
// The mobile app uses a parallel surface under /api/mobile (routes/mobile.php).
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
        Route::post('change-password', [AuthController::class, 'changePassword']);
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

    // Public website payment methods + bank details (COD is website-only).
    Route::get('payment-methods/{business_id}', [CheckoutController::class, 'paymentMethods']);

    // Public track order (order number + email/phone verification).
    Route::post('orders/{business_id}/track', [CustomerOrderController::class, 'track']);
});

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::post('reviews/{business_id}', [ProductReviewController::class, 'store']);

    // Customer account profile + addresses (scoped to business_id).
    Route::get('profile/{business_id}', [ProfileController::class, 'show']);
    Route::put('profile/{business_id}', [ProfileController::class, 'update']);
    Route::post('profile/{business_id}/addresses', [ProfileController::class, 'storeAddress']);
    Route::delete('profile/{business_id}/addresses/{address_id}', [ProfileController::class, 'destroyAddress']);

    // Customer orders (own orders only for the given business).
    Route::get('orders/{business_id}', [CustomerOrderController::class, 'index']);
    Route::get('orders/{business_id}/{order_id}', [CustomerOrderController::class, 'show']);

    // Website cart (authenticated, business-scoped).
    Route::get('cart/{business_id}', [CartController::class, 'show']);
    Route::post('cart/{business_id}', [CartController::class, 'store']);
    Route::put('cart/{business_id}/items/{cart_item_id}', [CartController::class, 'update']);
    Route::delete('cart/{business_id}/items/{cart_item_id}', [CartController::class, 'destroy']);
    Route::delete('cart/{business_id}', [CartController::class, 'clear']);

    // Website vouchers (authenticated, cart-scoped).
    Route::get('vouchers/{business_id}/search', [VoucherController::class, 'search']);
    Route::get('vouchers/{business_id}/eligible', [VoucherController::class, 'eligible']);
    Route::post('vouchers/{business_id}/preview', [VoucherController::class, 'preview']);
    Route::post('vouchers/{business_id}/apply', [VoucherController::class, 'apply']);
    Route::delete('vouchers/{business_id}', [VoucherController::class, 'remove']);

    // Website checkout / place order.
    Route::post('checkout/{business_id}', [CheckoutController::class, 'placeOrder']);

    // Wishlist (product-level and variation-level).
    Route::get('wishlist/{business_id}', [WishlistController::class, 'index']);
    Route::post('wishlist/{business_id}', [WishlistController::class, 'store']);
    Route::delete('wishlist/{business_id}', [WishlistController::class, 'destroy']);
    Route::post('wishlist/{business_id}/toggle', [WishlistController::class, 'toggle']);
    Route::get('wishlist/{business_id}/status', [WishlistController::class, 'status']);
});
