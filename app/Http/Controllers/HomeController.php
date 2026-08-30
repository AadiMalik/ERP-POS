<?php

namespace App\Http\Controllers;

use App\Enums\RoleNames;
use App\Services\Concrete\Admin\Dashboard\DashboardAccessService;
use App\Services\Concrete\Admin\Dashboard\DashboardService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected DashboardAccessService $access_service;
    protected DashboardService $dashboard_service;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(DashboardAccessService $access_service, DashboardService $dashboard_service)
    {
        $this->middleware('auth');
        $this->middleware('check.subscription');
        $this->access_service = $access_service;
        $this->dashboard_service = $dashboard_service;
    }

    /**
     * Show the Business Dashboard. Super Admin lands on the Subscription &
     * Billing dashboard instead; every other role gets the scope-resolved
     * dashboard (or a restricted placeholder if their role/permissions
     * don't grant access) via DashboardAccessService/DashboardService.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        if (getRoleName() == RoleNames::SUPERADMIN) {
            return redirect()->route('subscriptions.dashboard');
        }

        $business = auth()->user()->business;
        if ($business && app(\App\Services\Concrete\Admin\SubscriptionService::class)->isAccessRestricted($business)) {
            return redirect()->route('my-subscription.index');
        }

        $scope = $this->access_service->resolveScope($request);

        if (!$scope['allowed']) {
            return view('home', ['restricted' => true]);
        }

        return view('home', $this->dashboard_service->build($scope));
    }
}
