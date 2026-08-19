<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Lifecycle;

use App\Enums\RoleNames;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

abstract class BaseLifecycleReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::HRMANAGER,
        RoleNames::REPORTINGANALYST,
    ];

    abstract public function build(array $filters): Collection;

    abstract public function getData(array $filters);

    protected function resolveBusinessId(array $filters): ?string
    {
        return $filters['business_id'] ?? Auth::user()->business_id;
    }

    protected function scope(Builder $query, string $businessColumn = 'business_id', string $branchColumn = 'branch_id'): Builder
    {
        return applyRoleScope($query, $this->allow_roles, $businessColumn, $branchColumn);
    }
}
