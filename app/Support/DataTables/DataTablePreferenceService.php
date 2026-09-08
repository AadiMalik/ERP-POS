<?php

namespace App\Support\DataTables;

use App\Models\DataTablePreference;
use App\Support\DataTables\Contracts\DataTableDefinition;
use Illuminate\Support\Facades\Auth;

class DataTablePreferenceService
{
    public function __construct(protected DataTableAuthorizer $authorizer)
    {
    }

    public function find(string $tableKey): ?DataTablePreference
    {
        $userId = Auth::id();
        if (!$userId) {
            return null;
        }

        return DataTablePreference::where('user_id', $userId)
            ->where('table_key', $tableKey)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function stateFor(DataTableDefinition $definition): array
    {
        $row = $this->find($definition->key());
        $incoming = $row ? [
            'visible_columns' => $row->visible_columns,
            'column_order' => $row->column_order,
            'sort_column' => $row->sort_column,
            'sort_dir' => $row->sort_dir,
            'page_length' => $row->page_length,
            'export_columns' => $row->export_columns,
            'filters' => $row->filters,
        ] : [];

        return $this->authorizer->sanitizeState($definition, $incoming);
    }

    /**
     * @param  array<string, mixed>  $incoming
     */
    public function save(DataTableDefinition $definition, array $incoming): DataTablePreference
    {
        $state = $this->authorizer->sanitizeState($definition, $incoming);
        $user = Auth::user();

        $row = $this->find($definition->key());
        $payload = [
            'user_id' => $user->id,
            'business_id' => $user->business_id,
            'table_key' => $definition->key(),
            'visible_columns' => $state['visible_columns'],
            'column_order' => $state['column_order'],
            'sort_column' => $state['sort_column'],
            'sort_dir' => $state['sort_dir'],
            'page_length' => $state['page_length'],
            'export_columns' => $state['export_columns'],
            'filters' => $state['filters'],
            'updatedby_id' => $user->id,
            'date_updated' => now(),
        ];

        if ($row) {
            $row->update($payload);

            return $row->fresh();
        }

        $payload['datatable_preference_id'] = generateUuid();
        $payload['createdby_id'] = $user->id;
        $payload['date_created'] = now();

        return DataTablePreference::create($payload);
    }

    public function reset(DataTableDefinition $definition): array
    {
        $row = $this->find($definition->key());
        if ($row) {
            $row->delete();
        }

        return $this->authorizer->sanitizeState($definition, []);
    }

    /**
     * Layout for any listing DataTable (Yajra partial / client-side) that is
     * not a registered engine definition. Key is the HTML table id.
     *
     * @return array<string, mixed>
     */
    public function rawState(string $tableKey): array
    {
        $row = $this->find($tableKey);
        if (!$row) {
            return [];
        }

        return $this->sanitizeRaw($row->toArray());
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public function saveRaw(string $tableKey, array $incoming): array
    {
        $user = Auth::user();
        $state = $this->sanitizeRaw($incoming);
        $row = $this->find($tableKey);
        $payload = [
            'user_id' => $user->id,
            'business_id' => $user->business_id,
            'table_key' => $tableKey,
            'visible_columns' => $state['visible_columns'],
            'column_order' => $state['column_order'],
            'sort_column' => $state['sort_column'],
            'sort_dir' => $state['sort_dir'],
            'page_length' => $state['page_length'],
            'export_columns' => $state['export_columns'],
            'filters' => $state['filters'],
            'updatedby_id' => $user->id,
            'date_updated' => now(),
        ];

        if ($row) {
            $row->update($payload);

            return $this->sanitizeRaw($row->fresh()->toArray());
        }

        $payload['datatable_preference_id'] = generateUuid();
        $payload['createdby_id'] = $user->id;
        $payload['date_created'] = now();

        return $this->sanitizeRaw(DataTablePreference::create($payload)->toArray());
    }

    /**
     * @return array<string, mixed>
     */
    public function resetRaw(string $tableKey): array
    {
        $row = $this->find($tableKey);
        if ($row) {
            $row->delete();
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    protected function sanitizeRaw(array $incoming): array
    {
        $pageLengths = config('erp_datatables.page_lengths', [10, 25, 50, 100]);
        $pageLength = (int) ($incoming['page_length'] ?? 10);
        if (!in_array($pageLength, $pageLengths, true)) {
            $pageLength = (int) config('erp_datatables.default_page_length', 10);
        }

        $sortDir = strtolower((string) ($incoming['sort_dir'] ?? 'desc'));

        return [
            'visible_columns' => $this->stringList($incoming['visible_columns'] ?? []),
            'column_order' => $this->stringList($incoming['column_order'] ?? []),
            'sort_column' => is_string($incoming['sort_column'] ?? null) ? $incoming['sort_column'] : null,
            'sort_dir' => $sortDir === 'asc' ? 'asc' : 'desc',
            'page_length' => $pageLength,
            'export_columns' => $this->stringList($incoming['export_columns'] ?? []),
            'filters' => is_array($incoming['filters'] ?? null) ? $incoming['filters'] : [],
        ];
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    protected function stringList($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if ($item === null || $item === '') {
                continue;
            }
            $item = (string) $item;
            if (!in_array($item, $out, true)) {
                $out[] = $item;
            }
        }

        return $out;
    }
}
