<?php

use App\Http\Controllers\Api\Intro\BlogCategoryController;
use App\Http\Controllers\Api\Intro\BlogCommentController;
use App\Http\Controllers\Api\Intro\BlogController;
use App\Http\Controllers\Api\Intro\BlogTagController;
use App\Http\Controllers\Api\Intro\BusinessController;
use App\Http\Controllers\Api\Intro\ContactController;
use App\Http\Controllers\Api\Intro\HomepageController;
use App\Http\Controllers\Api\Intro\ModuleController;
use App\Http\Controllers\Api\Intro\NavigationController;
use App\Http\Controllers\Api\Intro\PackageController;
use App\Http\Controllers\Api\Intro\PageController;
use App\Http\Controllers\Api\Intro\TestimonialController;
use App\Http\Controllers\Api\Intro\WebsiteSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dukanaz Intro (marketing website) public API
| Prefix: /api/intro
|--------------------------------------------------------------------------
| Packages are read from the existing ERP `packages` table (not Intro CMS).
*/

Route::get('packages', [PackageController::class, 'index']);
Route::get('packages/{package_id}', [PackageController::class, 'show']);

Route::post('business-register', [BusinessController::class, 'register']);

Route::get('modules', [ModuleController::class, 'index']);
Route::get('modules/{slug}', [ModuleController::class, 'show']);

Route::get('blogs', [BlogController::class, 'index']);
Route::get('blogs/{slug}', [BlogController::class, 'show']);
Route::get('blog-categories', [BlogCategoryController::class, 'index']);
Route::get('blog-tags', [BlogTagController::class, 'index']);
Route::post('blog-comments', [BlogCommentController::class, 'store']);

Route::get('testimonials', [TestimonialController::class, 'index']);
Route::post('contact', [ContactController::class, 'store']);

Route::get('website-settings', [WebsiteSettingController::class, 'show']);
Route::get('navigation', [NavigationController::class, 'index']);
Route::get('pages', [PageController::class, 'index']);
Route::get('pages/{slug}', [PageController::class, 'show']);
Route::get('homepage', [HomepageController::class, 'show']);
