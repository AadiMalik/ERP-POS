<?php

use App\Http\Controllers\Api\Mobile\Auth\AuthController;
use App\Http\Controllers\Api\Mobile\BranchController;
use App\Http\Controllers\Api\Mobile\BrandController;
use App\Http\Controllers\Api\Mobile\CartController;
use App\Http\Controllers\Api\Mobile\CategoryController;
use App\Http\Controllers\Api\Mobile\CheckoutController;
use App\Http\Controllers\Api\Mobile\ContactMessageController;
use App\Http\Controllers\Api\Mobile\CustomerOrderController;
use App\Http\Controllers\Api\Mobile\LoyaltyController;
use App\Http\Controllers\Api\Mobile\NewsletterSubscriberController;
use App\Http\Controllers\Api\Mobile\PaymentController;
use App\Http\Controllers\Api\Mobile\ProductController;
use App\Http\Controllers\Api\Mobile\ProductReviewController;
use App\Http\Controllers\Api\Mobile\ProfileController;
use App\Http\Controllers\Api\Mobile\SocialMediaLinkController;
use App\Http\Controllers\Api\Mobile\VoucherController;
use App\Http\Controllers\Api\Mobile\WebsiteBenefitController;
use App\Http\Controllers\Api\Mobile\WebsiteFaqController;
use App\Http\Controllers\Api\Mobile\WebsiteHeroStatController;
use App\Http\Controllers\Api\Mobile\WebsiteHomeController;
use App\Http\Controllers\Api\Mobile\WebsitePageController;
use App\Http\Controllers\Api\Mobile\WebsiteSectionController;
use App\Http\Controllers\Api\Mobile\WebsiteSettingController;
use App\Http\Controllers\Api\Mobile\WebsiteTestimonialController;
use App\Http\Controllers\Api\Mobile\WebsiteThemeController;
use App\Http\Controllers\Api\Mobile\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile App Customer API
| Prefix: /api/mobile
|--------------------------------------------------------------------------
| Same surface as the website storefront API (`routes/api.php` /api/v1/*),
| but with dedicated Mobile controllers and services so the app can diverge
| later without touching the Vue storefront.
*/

Route::prefix('auth')->middleware('throttle:20,1')->group(function () {
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

Route::middleware('throttle:60,1')->group(function () {
    Route::get('website-theme/{business_id}', [WebsiteThemeController::class, 'show']);
    Route::get('website-settings/{business_id}', [WebsiteSettingController::class, 'show']);
    Route::get('branches/{business_id}', [BranchController::class, 'index']);
    Route::get('categories/{business_id}', [CategoryController::class, 'index']);
    Route::get('brands/{business_id}', [BrandController::class, 'index']);
    Route::get('products/{business_id}', [ProductController::class, 'index']);
    Route::get('products/{business_id}/{slug}', [ProductController::class, 'show']);

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

    Route::get('payment-methods/{business_id}', [CheckoutController::class, 'paymentMethods']);

    // Public active Payment Gateways for this business (Mobile App platform).
    Route::get('payment-gateways/{business_id}', [PaymentController::class, 'gateways']);

    Route::post('orders/{business_id}/track', [CustomerOrderController::class, 'track']);
});

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('reviews/{business_id}', [ProductReviewController::class, 'store']);

    Route::get('profile/{business_id}', [ProfileController::class, 'show']);
    Route::put('profile/{business_id}', [ProfileController::class, 'update']);
    Route::post('profile/{business_id}/addresses', [ProfileController::class, 'storeAddress']);
    Route::delete('profile/{business_id}/addresses/{address_id}', [ProfileController::class, 'destroyAddress']);

    Route::get('orders/{business_id}', [CustomerOrderController::class, 'index']);
    Route::get('orders/{business_id}/{order_id}', [CustomerOrderController::class, 'show']);

    // Customer Loyalty Program - balance summary + transaction history.
    Route::get('loyalty/{business_id}', [LoyaltyController::class, 'show']);
    Route::get('loyalty/{business_id}/history', [LoyaltyController::class, 'history']);

    Route::get('cart/{business_id}', [CartController::class, 'show']);
    Route::post('cart/{business_id}', [CartController::class, 'store']);
    Route::put('cart/{business_id}/items/{cart_item_id}', [CartController::class, 'update']);
    Route::delete('cart/{business_id}/items/{cart_item_id}', [CartController::class, 'destroy']);
    Route::delete('cart/{business_id}', [CartController::class, 'clear']);

    Route::get('vouchers/{business_id}/search', [VoucherController::class, 'search']);
    Route::get('vouchers/{business_id}/eligible', [VoucherController::class, 'eligible']);
    Route::post('vouchers/{business_id}/preview', [VoucherController::class, 'preview']);
    Route::post('vouchers/{business_id}/apply', [VoucherController::class, 'apply']);
    Route::delete('vouchers/{business_id}', [VoucherController::class, 'remove']);

    Route::post('checkout/{business_id}', [CheckoutController::class, 'placeOrder']);

    // Payment Gateway payment lifecycle for an already-created hold order.
    Route::post('orders/{business_id}/{order_id}/pay', [PaymentController::class, 'initiate']);
    Route::get('payments/{business_id}/{payment_transaction_id}', [PaymentController::class, 'status']);
    Route::post('payments/{business_id}/{payment_transaction_id}/verify', [PaymentController::class, 'verify']);

    Route::get('wishlist/{business_id}', [WishlistController::class, 'index']);
    Route::post('wishlist/{business_id}', [WishlistController::class, 'store']);
    Route::delete('wishlist/{business_id}', [WishlistController::class, 'destroy']);
    Route::post('wishlist/{business_id}/toggle', [WishlistController::class, 'toggle']);
    Route::get('wishlist/{business_id}/status', [WishlistController::class, 'status']);
});
