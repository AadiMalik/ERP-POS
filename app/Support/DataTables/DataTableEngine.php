<?php

namespace App\Support\DataTables;

use App\Support\DataTables\Contracts\DataTableDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\EloquentDataTable;

/**
 * Turns a DataTable definition + authorized state into a Yajra server-side
 * JSON response. Filtering, searching, sorting and pagination all stay in
 * SQL — rows are never collected in PHP for the list view.
 */
class DataTableEngine
{
    public function __construct(
        protected DataTableAuthorizer $authorizer,
        protected DataTableFilterEngine $filters
    ) {
    }

    public function process(DataTableDefinition $definition, Request $request, array $state): JsonResponse
    {
        $query = $definition->query();
        $this->filters->apply($query, $definition, $this->authorizer, $state['filters'] ?? []);

        $allowed = $this->columnsByKey($this->authorizer->allowedColumns($definition));
        $datatable = DataTables::of($query);

        $this->registerColumns($datatable, $allowed);
        $this->setRowId($datatable, $definition);

        $raw = [];
        foreach ($allowed as $column) {
            if (!empty($column['html'])) {
                $raw[] = $column['data'] ?? $column['key'];
            }
        }
        if ($raw) {
            $datatable->rawColumns(array_values(array_unique($raw)));
        }

        return $datatable->make(true);
    }

    /**
     * Same filtered query as the list, without Yajra paging — used by export.
     */
    public function filteredQuery(DataTableDefinition $definition, array $state)
    {
        $query = $definition->query();
        $this->filters->apply($query, $definition, $this->authorizer, $state['filters'] ?? []);
        $this->applySearch($query, $definition, $state['search'] ?? null);

        return $query;
    }

    /**
     * @param  array<string, array<string, mixed>>  $allowed
     */
    protected function registerColumns(EloquentDataTable $datatable, array $allowed): void
    {
        foreach ($allowed as $column) {
            $data = $column['data'] ?? $column['key'];
            $name = $column['name'] ?? $column['key'];

            if (is_callable($column['formatter'] ?? null)) {
                $datatable->addColumn($data, $column['formatter']);
            } elseif ($data !== $name && !isset($column['formatter'])) {
                $datatable->addColumn($data, function ($row) use ($data) {
                    return data_get($row, $data, '');
                });
            }

            if (!empty($column['searchable']) && !empty($column['name']) && $this->safeSqlName($column['name'])) {
                $sqlName = $column['name'];
                $datatable->filterColumn($data, function ($query, $keyword) use ($sqlName) {
                    $query->where($sqlName, 'like', '%' . $keyword . '%');
                });
            }

            if (!empty($column['orderable']) && !empty($column['name']) && $this->safeSqlName($column['name'])) {
                $sqlName = $column['name'];
                $datatable->orderColumn($data, function ($query, $order) use ($sqlName) {
                    $dir = strtolower((string) $order) === 'desc' ? 'desc' : 'asc';
                    $query->orderBy($sqlName, $dir);
                });
            }
        }
    }

    protected function setRowId(EloquentDataTable $datatable, DataTableDefinition $definition): void
    {
        $rowId = $definition->rowId();

        $datatable->setRowId(function ($row) use ($rowId) {
            return data_get($row, $rowId);
        });
    }

    /**
     * Apply the DataTables search box to export the same rows the list shows.
     */
    protected function applySearch($query, DataTableDefinition $definition, $search): void
    {
        $term = is_string($search) ? trim($search) : '';
        if ($term === '') {
            return;
        }

        $columns = [];
        foreach ($this->authorizer->allowedColumns($definition) as $column) {
            if (empty($column['searchable']) || empty($column['name']) || !$this->safeSqlName($column['name'])) {
                continue;
            }
            $columns[] = $column['name'];
        }
        if (!$columns) {
            return;
        }

        $query->where(function ($inner) use ($columns, $term) {
            foreach ($columns as $index => $name) {
                if ($index === 0) {
                    $inner->where($name, 'like', '%' . $term . '%');
                } else {
                    $inner->orWhere($name, 'like', '%' . $term . '%');
                }
            }
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $columns
     * @return array<string, array<string, mixed>>
     */
    protected function columnsByKey(array $columns): array
    {
        $map = [];
        foreach ($columns as $column) {
            $map[$column['key']] = $column;
        }

        return $map;
    }

    protected function safeSqlName(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_\.]+$/', $name);
    }
}
