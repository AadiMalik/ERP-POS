<?php

namespace App\Http\Middleware;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\SubscriptionSetting;
use App\Services\Concrete\Admin\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckBusinessSubscription
{
    protected SubscriptionService $subscription_service;

    public function __construct(SubscriptionService $subscription_service)
    {
        $this->subscription_service = $subscription_service;
    }

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

        $subscription = $this->subscription_service->getCurrentSubscription($business);
        $route_name = $request->route()?->getName();

        // Pending / under review / expired: stay logged in but only My Subscription
        // (and allowlisted account routes) so they can renew or await payment.
        if ($this->subscription_service->isAccessRestricted($business)) {
            $allowlist = $this->subscription_service->restrictedAccessAllowlist();

            if ($route_name && in_array($route_name, $allowlist, true)) {
                return $next($request);
            }

            return redirect()->route('my-subscription.index')
                ->with('error', 'Your subscription requires attention. Please renew or wait for payment confirmation to restore full access.');
        }

        // No subscription at all - treat the same as expired (nothing to fall back on)
        if (!$subscription) {
            Auth::logout();

            return redirect()->route('login')
                ->with('error', 'No active subscription found for your business.');
        }

        $status = $this->subscription_service->computeDisplayStatus($subscription);

        // Business suspended, or subscription explicitly cancelled
        if (in_array($status, [Status::SUSPENDED, Status::CANCELLED], true) || $business->status === Status::SUSPENDED) {
            Auth::logout();

            $message = $status === Status::CANCELLED
                ? 'Your subscription has been cancelled.'
                : 'Your business has been suspended.';

            return redirect()->route('login')->with('error', $message);
        }

        // Grace period: never log the business out or touch its data, but
        // optionally block write requests until they renew (configurable).
        if ($status === Status::GRACE_PERIOD) {
            $settings = SubscriptionSetting::current();
            $is_write_request = $request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch') || $request->isMethod('delete');

            if ($settings->restrict_access_in_grace_period && $is_write_request) {
                $allowlist = $settings->restricted_route_names ?? [];

                if (!in_array($route_name, $allowlist)) {
                    abort(403, 'Your subscription is in the grace period. Please renew to continue.');
                }
            }
        }

        return $next($request);
    }
}
