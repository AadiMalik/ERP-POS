<?php

namespace App\Http\Controllers;

use App\Enums\RoleNames;
use App\Services\Concrete\Admin\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    protected SubscriptionService $subscription_service;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(SubscriptionService $subscription_service)
    {
        $this->middleware('auth');
        $this->subscription_service = $subscription_service;
    }

    /**
     * Show the application dashboard. Super Admin lands on the
     * Subscription & Billing dashboard; every other user gets the regular
     * home page with their business's subscription card at the top.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if (getRoleName() == RoleNames::SUPERADMIN) {
            return redirect()->route('subscriptions.dashboard');
        }

        $business = Auth::user()->business;
        $subscription = $business ? $this->subscription_service->getCurrentSubscription($business) : null;
        $display_status = $subscription ? $this->subscription_service->computeDisplayStatus($subscription) : null;

        return view('home', compact('subscription', 'display_status'));
    }
}
