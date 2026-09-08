<?php

namespace App\Support\DataTables;

use App\Services\Concrete\Admin\AccessControlService;
use App\Services\Concrete\Admin\FeatureLimitService;
use App\Support\DataTables\Contracts\DataTableDefinition;
use Illuminate\Support\Facades\Auth;

/**
 * Column / filter / table access for the DataTable engine. Never trust the
 * client: preferences, visible columns, and export column lists are always
 * re-checked against this class before a query or file is produced.
 */
class DataTableAuthorizer
{
    public function __construct(protected AccessControlService $access)
    {
    }

    public function canAccess(DataTableDefinition $definition): bool
    {
        if (!$this->access->allows($definition->permission())) {
            return false;
        }

        return $this->moduleAllowed($definition);
    }

    public function canExport(DataTableDefinition $definition): bool
    {
        if (!$this->access->allows($definition->exportPermission())) {
            return false;
        }

        return $this->moduleAllowed($definition);
    }

    protected function moduleAllowed(DataTableDefinition $definition): bool
    {
        $module = $definition->moduleKey();
        if (!$module) {
            return true;
        }

        return app(FeatureLimitService::class)->hasModule($module);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allowedColumns(DataTableDefinition $definition): array
    {
        return array_values(array_filter(
            $definition->columns(),
            fn ($column) => $this->allowsColumn($column)
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allowedFilters(DataTableDefinition $definition): array
    {
        return array_values(array_filter(
            $definition->filters(),
            fn ($filter) => $this->allowsFilter($filter)
        ));
    }

    public function allowsColumn(array $column): bool
    {
        $permission = $column['permission'] ?? null;

        if (!$permission) {
            return true;
        }

        return $this->access->allows($permission);
    }

    public function allowsFilter(array $filter): bool
    {
        $permission = $filter['permission'] ?? null;

        if (!$permission) {
            return true;
        }

        return $this->access->allows($permission);
    }

    /**
     * Drop keys the current user is not allowed to see, keep a stable order,
     * and never let action/checkbox columns be exported.
     *
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public function sanitizeState(DataTableDefinition $definition, array $incoming): array
    {
        $allowed = $this->allowedColumns($definition);
        $allowedKeys = array_column($allowed, 'key');
        $allowedMap = [];
        foreach ($allowed as $column) {
            $allowedMap[$column['key']] = $column;
        }

        $defaultVisible = [];
        foreach ($allowed as $column) {
            if (!empty($column['visible'])) {
                $defaultVisible[] = $column['key'];
            }
        }

        $columnOrder = $this->intersectKeys($incoming['column_order'] ?? $allowedKeys, $allowedKeys);
        foreach ($allowedKeys as $key) {
            if (!in_array($key, $columnOrder, true)) {
                $columnOrder[] = $key;
            }
        }

        $alwaysKeys = [];
        foreach ($allowed as $column) {
            if (!empty($column['always'])) {
                $alwaysKeys[] = $column['key'];
            }
        }

        $visible = $this->intersectKeys($incoming['visible_columns'] ?? $defaultVisible, $allowedKeys);
        foreach ($alwaysKeys as $key) {
            if (!in_array($key, $visible, true)) {
                $visible[] = $key;
            }
        }
        if (empty($visible)) {
            $visible = $defaultVisible ?: $allowedKeys;
        }

        $exportableKeys = [];
        foreach ($allowed as $column) {
            if (!empty($column['exportable'])) {
                $exportableKeys[] = $column['key'];
            }
        }
        $defaultExport = array_values(array_filter($defaultVisible, function ($key) use ($exportableKeys) {
            return in_array($key, $exportableKeys, true);
        }));
        $exportColumns = $this->intersectKeys($incoming['export_columns'] ?? $defaultExport, $exportableKeys);
        if (empty($exportColumns)) {
            $exportColumns = $defaultExport ?: $exportableKeys;
        }

        $sortColumn = $incoming['sort_column'] ?? ($definition->defaultSort()['column'] ?? null);
        if (!$sortColumn || !isset($allowedMap[$sortColumn]) || empty($allowedMap[$sortColumn]['orderable'])) {
            $sortColumn = $definition->defaultSort()['column'] ?? ($allowedKeys[0] ?? null);
        }

        $sortDir = strtolower((string) ($incoming['sort_dir'] ?? ($definition->defaultSort()['dir'] ?? 'desc')));
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $pageLengths = config('erp_datatables.page_lengths', [10, 25, 50, 100]);
        $pageLength = (int) ($incoming['page_length'] ?? $definition->defaultPageLength());
        if (!in_array($pageLength, $pageLengths, true)) {
            $pageLength = $definition->defaultPageLength();
        }

        $allowedFilterKeys = array_column($this->allowedFilters($definition), 'key');
        $filters = [];
        foreach (($incoming['filters'] ?? []) as $key => $value) {
            if (in_array($key, $allowedFilterKeys, true) && $this->filterHasValue($value)) {
                $filters[$key] = $value;
            }
        }

        return [
            'visible_columns' => array_values($visible),
            'column_order' => array_values($columnOrder),
            'sort_column' => $sortColumn,
            'sort_dir' => $sortDir,
            'page_length' => $pageLength,
            'export_columns' => array_values($exportColumns),
            'filters' => $filters,
        ];
    }

    /**
     * @param  array<int, string>|mixed  $incoming
     * @param  array<int, string>  $allowed
     * @return array<int, string>
     */
    protected function intersectKeys($incoming, array $allowed): array
    {
        if (!is_array($incoming)) {
            return [];
        }

        $out = [];
        foreach ($incoming as $key) {
            $key = (string) $key;
            if (in_array($key, $allowed, true) && !in_array($key, $out, true)) {
                $out[] = $key;
            }
        }

        return $out;
    }

    protected function filterHasValue($value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        if (is_array($value)) {
            $filtered = array_filter($value, function ($item) {
                return $item !== null && $item !== '';
            });
            return count($filtered) > 0;
        }

        return true;
    }

    public function userId(): ?int
    {
        return Auth::id();
    }
}
