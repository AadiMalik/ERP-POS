<?php

namespace App\Support\DataTables;

use App\Enums\RoleNames;
use App\Support\DataTables\Contracts\DataTableDefinition;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies authorized filter values to an Eloquent query at the database
 * level. Each filter type has a default SQL mapping; a definition may
 * override with an `apply` closure for module-specific rules.
 */
class DataTableFilterEngine
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function apply(Builder $query, DataTableDefinition $definition, DataTableAuthorizer $authorizer, array $values): Builder
    {
        $filters = $authorizer->allowedFilters($definition);

        foreach ($filters as $filter) {
            $key = $filter['key'];
            $value = $values[$key] ?? null;
            if (!$this->hasValue($value)) {
                continue;
            }

            if (is_callable($filter['apply'] ?? null)) {
                $filter['apply']($query, $value, $filter);
                continue;
            }

            $this->applyByType($query, $filter, $value);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    protected function applyByType(Builder $query, array $filter, $value): void
    {
        $column = $filter['column'] ?? $filter['key'];
        $type = $filter['type'] ?? 'text';

        switch ($type) {
            case 'text':
                $query->where($column, 'like', '%' . $this->escapeLike((string) $value) . '%');
                break;

            case 'select':
            case 'status':
            case 'branch':
            case 'business':
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
                break;

            case 'multi_select':
                $ids = is_array($value) ? $value : explode(',', (string) $value);
                $ids = array_values(array_filter($ids, fn ($id) => $id !== null && $id !== ''));
                if ($ids) {
                    $query->whereIn($column, $ids);
                }
                break;

            case 'date_range':
                $start = is_array($value) ? ($value['start'] ?? $value['start_date'] ?? null) : null;
                $end = is_array($value) ? ($value['end'] ?? $value['end_date'] ?? null) : null;
                if ($start) {
                    $query->where($column, '>=', businessStartOfDay($start));
                }
                if ($end) {
                    $query->where($column, '<=', businessEndOfDay($end));
                }
                break;

            case 'numeric_range':
                $min = is_array($value) ? ($value['min'] ?? null) : null;
                $max = is_array($value) ? ($value['max'] ?? null) : null;
                if ($min !== null && $min !== '') {
                    $query->where($column, '>=', $min);
                }
                if ($max !== null && $max !== '') {
                    $query->where($column, '<=', $max);
                }
                break;

            default:
                $query->where($column, $value);
                break;
        }
    }

    public function hasValue($value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($item !== null && $item !== '') {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }

    /**
     * Super Admin is the only role that should see a cross-business filter.
     */
    public static function isSuperAdmin(): bool
    {
        return getRoleName() === RoleNames::SUPERADMIN;
    }
}
