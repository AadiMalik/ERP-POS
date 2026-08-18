<?php

namespace App\Http\Middleware;

use App\Services\Concrete\Admin\FeatureLimitService;
use Closure;
use Illuminate\Http\Request;

/**
 * Route-group-level package feature gate, e.g. Route::group(['middleware' =>
 * ['module:hrm']], ...) - mirrors how the whole POS surface is gated by the
 * 'permission:pos.access' route-group middleware. Kept as real request
 * middleware (not inline controller-constructor code) so it never runs
 * outside an actual HTTP request (e.g. `route:list`), where Auth::user()
 * would be null.
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module)
    {
        if (!app(FeatureLimitService::class)->hasModule($module)) {
            abort(403, 'This module is not enabled on your subscription package.');
        }

        return $next($request);
    }
}
