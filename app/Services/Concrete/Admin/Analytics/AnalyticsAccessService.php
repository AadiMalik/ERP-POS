<?php

namespace App\Services\Concrete\Admin\Analytics;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\Product;
use App\Services\Concrete\Admin\Dashboard\DashboardAccessService;
use Illuminate\Http\Request;

/**
 * Resolves the Analytics dashboard's access scope. Delegates the actual
 * role-tier/branch/date resolution entirely to DashboardAccessService so
 * Analytics and the Home Dashboard always agree on who can see what - this
 * only adds the extra filter dimensions (product/category/brand/customer)
 * the Analytics filter bar offers beyond the Home dashboard's order_type/
 * order_source/payment_method.
 */
class AnalyticsAccessService
{
    public function __construct(protected DashboardAccessService $dashboard_access)
    {
    }

    public function resolveScope(Request $request): array
    {
        $scope = $this->dashboard_access->resolveScope($request);

        if (!$scope['allowed']) {
            return $scope;
        }

        $business_id = $scope['business_id'];

        $scope['product_id'] = $this->dashboard_access->resolveLookupId(
            Product::class, 'product_id', $business_id, $request->input('product_id')
        );
        $scope['category_id'] = $this->dashboard_access->resolveLookupId(
            Category::class, 'category_id', $business_id, $request->input('category_id')
        );
        $scope['brand_id'] = $this->dashboard_access->resolveLookupId(
            Brand::class, 'brand_id', $business_id, $request->input('brand_id')
        );
        // Customers aren't their own model - CustomerProfile is the
        // business-scoped record; validating against it (keyed by user_id)
        // reuses the exact same anti-tampering technique while resolving to
        // the orders.user_id value every report/service already filters by.
        $scope['customer_id'] = $this->dashboard_access->resolveLookupId(
            CustomerProfile::class, 'user_id', $business_id, $request->input('customer_id')
        );

        // Whether the previous-period comparison arrows should be computed -
        // opt-in via a checkbox in the filter bar, off by default to save a
        // second round of queries when the user just wants the raw numbers.
        $scope['compare_previous_period'] = $request->boolean('compare_previous_period');

        return $scope;
    }
}
