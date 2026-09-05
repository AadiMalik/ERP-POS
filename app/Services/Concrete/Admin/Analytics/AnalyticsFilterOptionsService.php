<?php

namespace App\Services\Concrete\Admin\Analytics;

use App\Enums\Status;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\Product;
use App\Services\Concrete\Admin\Dashboard\DashboardFilterOptionsService;

/**
 * Dropdown option lists for the Analytics filter bar: everything the Home
 * Dashboard's filter bar already offers (order type/source/payment method),
 * plus the extra dimensions Analytics adds (product/category/brand/customer).
 * Scoped to the resolved scope's business, never to raw request input.
 */
class AnalyticsFilterOptionsService
{
    public function __construct(protected DashboardFilterOptionsService $dashboard_filter_options)
    {
    }

    public function build(array $scope): array
    {
        $business_id = $scope['business_id'];

        return array_merge($this->dashboard_filter_options->build($scope), [
            'products' => Product::where('business_id', $business_id)
                ->where('status', Status::ACTIVE)
                ->where('is_deleted', 0)
                ->orderBy('name')
                ->get(['product_id', 'name']),
            'categories' => Category::where('business_id', $business_id)
                ->where('status', Status::ACTIVE)
                ->where('is_deleted', 0)
                ->orderBy('name')
                ->get(['category_id', 'name']),
            'brands' => Brand::where('business_id', $business_id)
                ->where('status', Status::ACTIVE)
                ->where('is_deleted', 0)
                ->orderBy('name')
                ->get(['brand_id', 'name']),
            'customers' => CustomerProfile::where('business_id', $business_id)
                ->where('is_deleted', 0)
                ->where('is_walkin', 0)
                ->with('user:id,name')
                ->get()
                ->map(fn ($profile) => [
                    'user_id' => $profile->user_id,
                    'name' => $profile->user->name ?? $profile->company_name ?? 'Unknown',
                ])
                ->sortBy('name')
                ->values(),
        ]);
    }
}
