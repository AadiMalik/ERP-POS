<?php

namespace App\Http\Middleware;

use App\Enums\RoleNames;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckBusinessSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Skip for super admin
        if (getRoleName() == RoleNames::SUPERADMIN) {
            return $next($request);
        }

        // Check business existence
        if (!$user->business_id) {
            Auth::logout();

            return redirect()->route('login')
                ->with('error', 'Business not found.');
        }

        $business = $user->business;

        // Business not found
        if (!$business) {
            Auth::logout();

            return redirect()->route('login')
                ->with('error', 'Business not found.');
        }

        // Business suspended
        if ($business->status == 'suspended') {
            Auth::logout();

            return redirect()->route('login')
                ->with('error', 'Your business has been suspended.');
        }

        // Subscription expired
        if (
            empty($business->subscription_end) ||
            Carbon::parse($business->subscription_end)->lt(now())
        ) {

            Auth::logout();

            return redirect()->route('login')
                ->with('error', 'Your subscription has expired.');
        }

        return $next($request);
    }
}
