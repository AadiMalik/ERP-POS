<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Employee;

use App\Enums\RoleNames;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Shared business/branch-scoping helpers for every Employee & Organization
 * report service. Concrete services implement build() (raw rows, used by
 * print/pdf/export) and getData() (DataTables JSON, used by the index
 * table) - the same two-method split every finance report service already
 * uses (see ExpenseReportService).
 */
abstract class BaseEmployeeReportService
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
