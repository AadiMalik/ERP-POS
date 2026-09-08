<?php

namespace App\Support\DataTables;

use App\Support\DataTables\Contracts\DataTableDefinition;
use Illuminate\Http\Request;

/**
 * Facade used by controllers and Blade: resolve a table key, enforce
 * permissions, merge stored preferences with the request, and hand off to
 * the engine / export services.
 */
class DataTableManager
{
    public function __construct(
        protected DataTableAuthorizer $authorizer,
        protected DataTablePreferenceService $preferences,
        protected DataTableEngine $engine,
        protected DataTableExportService $exports
    ) {
    }

    public function definition(string $key): DataTableDefinition
    {
        return DataTableRegistry::resolve($key);
    }

    public function authorize(DataTableDefinition $definition, bool $export = false): void
    {
        if ($export) {
            abort_unless($this->authorizer->canExport($definition), 403);
            return;
        }

        abort_unless($this->authorizer->canAccess($definition), 403);
    }

    /**
     * Payload the shared listing component / JS needs to render filters and columns.
     *
     * @return array<string, mixed>
     */
    public function bootstrap(string $key): array
    {
        $definition = $this->definition($key);
        $this->authorize($definition);

        $state = $this->preferences->stateFor($definition);
        $listing = $definition->listing() ?: [];

        return [
            'key' => $definition->key(),
            'label' => $definition->label(),
            'row_id' => $definition->rowId(),
            'can_export' => $this->listingCanExport($listing, $definition),
            'columns' => $this->publicColumns($definition),
            'filters' => $this->publicFilters($definition),
            'state' => $state,
            'page' => $listing,
            'page_lengths' => config('erp_datatables.page_lengths', [10, 25, 50, 100]),
            'urls' => [
                'data' => url('admin/datatable/' . $definition->key() . '/data'),
                'preferences' => url('admin/datatable/' . $definition->key() . '/preferences'),
            ],
            'locale' => datatablesLocaleCode(),
        ];
    }

    /**
     * Merge request filters/state with stored preferences, then run Yajra.
     */
    public function data(string $key, Request $request)
    {
        $definition = $this->definition($key);
        $this->authorize($definition);

        $state = $this->stateFromRequest($definition, $request);

        return $this->engine->process($definition, $request, $state);
    }

    public function savePreferences(string $key, Request $request): array
    {
        $definition = $this->definition($key);
        $this->authorize($definition);

        $row = $this->preferences->save($definition, $this->incomingState($request));

        return $this->authorizer->sanitizeState($definition, $row->toArray());
    }

    public function resetPreferences(string $key): array
    {
        $definition = $this->definition($key);
        $this->authorize($definition);

        return $this->preferences->reset($definition);
    }

    public function export(string $key, Request $request)
    {
        $definition = $this->definition($key);
        $this->authorize($definition, true);

        $state = $this->stateFromRequest($definition, $request);
        $format = $request->input('export_format', 'xlsx') === 'csv' ? 'csv' : 'xlsx';

        return $this->exports->download($definition, $state, $format);
    }

    /**
     * @return array<string, mixed>
     */
    public function stateFromRequest(DataTableDefinition $definition, Request $request): array
    {
        $stored = $this->preferences->stateFor($definition);
        $incoming = $this->incomingState($request);

        $merged = array_merge($stored, array_filter($incoming, function ($value, $key) {
            if ($key === 'filters') {
                return is_array($value);
            }
            return $value !== null && $value !== '';
        }, ARRAY_FILTER_USE_BOTH));

        if (isset($incoming['filters']) && is_array($incoming['filters'])) {
            $merged['filters'] = $incoming['filters'];
        }

        // Legacy date-range fields used by the existing global date filter widget.
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $merged['filters'] = $merged['filters'] ?? [];
            if (!isset($merged['filters']['date_created'])) {
                $merged['filters']['date_created'] = [];
            }
            if ($request->filled('start_date')) {
                $merged['filters']['date_created']['start'] = $request->input('start_date');
            }
            if ($request->filled('end_date')) {
                $merged['filters']['date_created']['end'] = $request->input('end_date');
            }
        }

        $state = $this->authorizer->sanitizeState($definition, $merged);
        $search = $incoming['search'] ?? null;
        $state['search'] = is_string($search) ? $search : '';

        return $state;
    }

    /**
     * Export button and any export-related Customize UI only when this listing
     * has an import/export module, has_export is not false, and the user may
     * export. Driven once here so each CRUD does not duplicate the check.
     */
    protected function listingCanExport(array $listing, DataTableDefinition $definition): bool
    {
        if (empty($listing['import_export_module'])) {
            return false;
        }

        if (array_key_exists('has_export', $listing) && !$listing['has_export']) {
            return false;
        }

        return $this->authorizer->canExport($definition);
    }

    /**
     * @return array<string, mixed>
     */
    protected function incomingState(Request $request): array
    {
        $filters = $request->input('filters', []);
        if (is_string($filters)) {
            $decoded = json_decode($filters, true);
            $filters = is_array($decoded) ? $decoded : [];
        }

        $visible = $request->input('visible_columns', $request->input('columns_visible'));
        if (is_string($visible)) {
            $visible = json_decode($visible, true);
        }
        $order = $request->input('column_order');
        if (is_string($order)) {
            $order = json_decode($order, true);
        }
        $export = $request->input('export_columns');
        if (is_string($export)) {
            $export = json_decode($export, true);
        }

        return [
            'visible_columns' => $visible,
            'column_order' => $order,
            'sort_column' => $request->input('sort_column'),
            'sort_dir' => $request->input('sort_dir'),
            'page_length' => $request->input('page_length'),
            'export_columns' => $export,
            'filters' => is_array($filters) ? $filters : [],
            'search' => is_string($request->input('search'))
                ? $request->input('search')
                : data_get($request->input('search'), 'value'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function publicColumns(DataTableDefinition $definition): array
    {
        $out = [];
        foreach ($this->authorizer->allowedColumns($definition) as $column) {
            $out[] = [
                'key' => $column['key'],
                'label' => $column['label'],
                'data' => $column['data'] ?? $column['key'],
                'name' => $column['name'] ?? $column['key'],
                'searchable' => (bool) ($column['searchable'] ?? true),
                'orderable' => (bool) ($column['orderable'] ?? true),
                'visible' => (bool) ($column['visible'] ?? true),
                'exportable' => (bool) ($column['exportable'] ?? true),
                'html' => (bool) ($column['html'] ?? false),
                'always' => (bool) ($column['always'] ?? false),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function publicFilters(DataTableDefinition $definition): array
    {
        $out = [];
        foreach ($this->authorizer->allowedFilters($definition) as $filter) {
            $out[] = [
                'key' => $filter['key'],
                'type' => $filter['type'],
                'label' => $filter['label'],
                'placeholder' => $filter['placeholder'] ?? null,
                'options' => $filter['options'] ?? [],
            ];
        }

        return $out;
    }
}
