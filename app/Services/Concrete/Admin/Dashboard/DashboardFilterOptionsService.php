<?php

namespace App\Services\Concrete\Admin\Dashboard;

use App\Enums\Status;
use App\Models\OrderSource;
use App\Models\OrderType;
use App\Models\PaymentMethod;

/**
 * Dropdown option lists for the dashboard's filter bar, scoped to the
 * resolved scope's business (never to raw request input).
 */
class DashboardFilterOptionsService
{
    public function build(array $scope): array
    {
        $business_id = $scope['business_id'];

        return [
            'order_types' => OrderType::where('business_id', $business_id)
                ->where('status', Status::ACTIVE)
                ->where('is_deleted', 0)
                ->orderBy('sort_order')
                ->get(['order_type_id', 'name']),
            'order_sources' => OrderSource::where('business_id', $business_id)
                ->where('status', Status::ACTIVE)
                ->where('is_deleted', 0)
                ->orderBy('sort_order')
                ->get(['order_source_id', 'name']),
            'payment_methods' => PaymentMethod::where('business_id', $business_id)
                ->where('status', Status::ACTIVE)
                ->where('is_deleted', 0)
                ->orderBy('sort_order')
                ->get(['payment_method_id', 'name']),
        ];
    }
}
