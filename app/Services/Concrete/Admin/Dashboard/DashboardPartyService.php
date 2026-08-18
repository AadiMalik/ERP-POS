<?php

namespace App\Services\Concrete\Admin\Dashboard;

use App\Enums\Status;
use App\Models\CustomerProfile;
use App\Models\Supplier;
use Carbon\Carbon;

/**
 * Customer/Supplier summary counts for a dashboard scope. Customers are
 * `users` rows extended by CustomerProfile (no standalone Customer model),
 * scoped the same way CustomerService::getAllActive() does. Suppliers carry
 * branch_id directly - the simplest branch scoping in the whole dashboard.
 */
class DashboardPartyService
{
    public function build(array $scope): array
    {
        return [
            'customers' => $this->customerSummary($scope),
            'suppliers' => $this->supplierSummary($scope),
        ];
    }

    protected function customerSummary(array $scope): array
    {
        $query = fn () => CustomerProfile::query()
            ->where('business_id', $scope['business_id'])
            ->where('is_deleted', 0)
            ->when($scope['effective_branch_id'], fn ($q, $b) => $q->where('branch_id', $b));

        return [
            'total' => $query()->count(),
            'active' => $query()->where('status', Status::ACTIVE)->count(),
            'new_this_period' => $query()->where('date_created', '>=', Carbon::now()->startOfMonth())->count(),
        ];
    }

    protected function supplierSummary(array $scope): array
    {
        $query = fn () => Supplier::query()
            ->where('business_id', $scope['business_id'])
            ->where('is_deleted', 0)
            ->when($scope['effective_branch_id'], fn ($q, $b) => $q->where('branch_id', $b));

        return [
            'total' => $query()->count(),
            'active' => $query()->where('status', Status::ACTIVE)->count(),
            'new_this_period' => $query()->where('date_created', '>=', Carbon::now()->startOfMonth())->count(),
        ];
    }
}
