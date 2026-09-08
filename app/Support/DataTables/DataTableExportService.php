<?php

namespace App\Support\DataTables;

use App\Exports\DataTable\GenericDataTableExport;
use App\Support\DataTables\Contracts\DataTableDefinition;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataTableExportService
{
    public function __construct(
        protected DataTableEngine $engine,
        protected DataTableAuthorizer $authorizer
    ) {
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function download(
        DataTableDefinition $definition,
        array $state,
        string $format
    ): BinaryFileResponse {
        $allowed = $this->authorizer->allowedColumns($definition);
        $allowedByKey = [];
        foreach ($allowed as $column) {
            $allowedByKey[$column['key']] = $column;
        }

        $keys = $this->resolveExportKeys($state, $allowedByKey);
        $exportColumns = [];
        foreach ($keys as $key) {
            if (!isset($allowedByKey[$key]) || empty($allowedByKey[$key]['exportable'])) {
                continue;
            }
            $column = $allowedByKey[$key];
            $exportColumns[] = [
                'key' => $key,
                'label' => $column['label'],
                'export_value' => function ($row) use ($column) {
                    if (is_callable($column['export_formatter'] ?? null)) {
                        return $column['export_formatter']($row);
                    }
                    if (is_callable($column['formatter'] ?? null) && empty($column['html'])) {
                        return $column['formatter']($row);
                    }
                    $value = data_get($row, $column['data'] ?? $column['key'], '');
                    if (is_scalar($value) || $value === null) {
                        return $value;
                    }

                    return '';
                },
            ];
        }

        $limit = (int) config('erp_datatables.export_row_limit', 50000);
        $query = $this->engine->filteredQuery($definition, $state);
        $rows = $query->limit($limit)->get();

        $filename = $definition->key() . '-' . now()->format('Ymd-His') . '.' . ($format === 'csv' ? 'csv' : 'xlsx');
        $export = new GenericDataTableExport($rows, $exportColumns);

        if ($format === 'csv') {
            return Excel::download($export, $filename, ExcelFormat::CSV);
        }

        return Excel::download($export, $filename);
    }

    /**
     * Columns currently showing on the table (and the user may export).
     * Action / HTML-only columns are skipped.
     *
     * @param  array<string, mixed>  $state
     * @param  array<string, array<string, mixed>>  $allowedByKey
     * @return array<int, string>
     */
    protected function resolveExportKeys(array $state, array $allowedByKey): array
    {
        $exportable = [];
        foreach ($allowedByKey as $key => $column) {
            if (!empty($column['exportable'])) {
                $exportable[] = $key;
            }
        }

        $requested = $state['export_columns'] ?? [];
        if (!$requested) {
            $requested = $state['visible_columns'] ?? [];
        }

        $keys = [];
        foreach ((array) $requested as $key) {
            if (in_array($key, $exportable, true) && !in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        return $keys ?: $exportable;
    }
}
