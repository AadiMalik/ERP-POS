<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Enums\RoleNames;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

abstract class BasePayrollFinanceReportService
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

    /**
     * @return array{0: string, 1: string} business-local Y-m-d bounds
     */
    protected function calendarRange(array $filters, string $defaultStart = 'year'): array
    {
        $start = !empty($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->toDateString()
            : ($defaultStart === 'month'
                ? Carbon::now(businessTimezone())->startOfMonth()->toDateString()
                : Carbon::now(businessTimezone())->startOfYear()->toDateString());
        $end = !empty($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->toDateString()
            : businessToday();

        return [$start, $end];
    }
}
