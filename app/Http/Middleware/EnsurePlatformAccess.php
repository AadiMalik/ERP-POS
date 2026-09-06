<?php

namespace App\Http\Middleware;

use App\Enums\RoleNames;
use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Route-group-level Business Access Control gate, e.g. Route::group(['middleware'
 * => ['platform:pos']], ...) - mirrors EnsureModuleEnabled, but checks the
 * Super-Admin-controlled whole-business platform switches on `businesses`
 * instead of the package/subscription module tier. Website and Mobile App
 * share the 'storefront' key: they serve the exact same /api/mobile route
 * surface today with no client-identifying signal to enforce them separately.
 * ponytail: split 'storefront' into 'website'/'mobile' once those frontends
 * send a client-platform header the backend can key off of.
 */
class EnsurePlatformAccess
{
    protected const COLUMN = [
        'storefront' => 'storefront_access_enabled',
        'pos' => 'pos_access_enabled',
        'offline-pos' => 'offline_pos_access_enabled',
    ];

    protected const MESSAGE = [
        'storefront' => 'This business\'s Website & Mobile App access has been disabled. Please contact the administrator.',
        'pos' => 'Your business access to POS has been disabled. Please contact the administrator.',
        'offline-pos' => 'Your business access to Offline POS has been disabled. Please contact the administrator.',
    ];

    public function handle(Request $request, Closure $next, string $platform)
    {
        if (Auth::check() && getRoleName() === RoleNames::SUPERADMIN) {
            return $next($request);
        }

        $businessId = $request->route('business_id') ?? Auth::user()?->business_id;

        if (!$businessId) {
            return $next($request);
        }

        $enabled = Business::where('business_id', $businessId)->value(self::COLUMN[$platform]);

        // A raw value() read returns the driver's native type (e.g. int 0/1),
        // not a PHP bool, so this must be a loose falsy check - `=== false`
        // never matches int(0). `null` (business_id didn't resolve to a real
        // business) is left alone so the controller's own validation reports
        // that, rather than this middleware misreporting it as "disabled".
        if ($enabled !== null && !$enabled) {
            abort(403, self::MESSAGE[$platform]);
        }

        return $next($request);
    }
}
