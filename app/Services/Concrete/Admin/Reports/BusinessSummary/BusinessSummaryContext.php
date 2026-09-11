<?php

namespace App\Services\Concrete\Admin\Reports\BusinessSummary;

/**
 * Shared reporting window, module flags, thresholds and filter bag used by
 * every Business Summary provider. Established once per request so date,
 * timezone, branch and permission checks are not re-derived per metric.
 */
class BusinessSummaryContext
{
    public function __construct(
        public string $business_id,
        public ?string $branch_id,
        public string $start_date,
        public string $end_date,
        public string $previous_start_date,
        public string $previous_end_date,
        public string $timezone,
        public string $business_name,
        public bool $is_single_day,
        public array $modules,
        public array $thresholds,
        public array $permissions,
        public array $filter_obj,
        public array $previous_filter_obj
    ) {
    }

    public function hasModule(string $key): bool
    {
        return (bool) ($this->modules[$key] ?? false);
    }

    public function can(string $permission): bool
    {
        return (bool) ($this->permissions[$permission] ?? false);
    }

    public function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public function threshold(string $key, $default = null)
    {
        return $this->thresholds[$key] ?? $default;
    }

    public function actionUrl(string $path, array $extra = []): string
    {
        $params = array_filter([
            'business_id' => $this->business_id,
            'branch_id' => $this->branch_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ], fn ($value) => $value !== null && $value !== '');

        return url($path) . '?' . http_build_query(array_merge($params, $extra));
    }
}
