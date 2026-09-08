<?php

namespace App\Support\DataTables;

use App\Enums\RoleNames;
use App\Support\DataTables\Contracts\DataTableDefinition;
use Illuminate\Database\Eloquent\Builder;

abstract class AbstractDataTable implements DataTableDefinition
{
    abstract public function key(): string;

    abstract public function label(): string;

    abstract public function permission(): string;

    abstract public function query(): Builder;

    abstract public function columns(): array;

    public function exportPermission(): string
    {
        return preg_replace('/\.view$/', '.export', $this->permission()) ?: $this->permission();
    }

    public function filters(): array
    {
        return [];
    }

    public function rowId(): string
    {
        return 'id';
    }

    public function moduleKey(): ?string
    {
        return null;
    }

    public function defaultSort(): array
    {
        return ['column' => 'date_created', 'dir' => 'desc'];
    }

    public function defaultPageLength(): int
    {
        return (int) config('erp_datatables.default_page_length', 10);
    }

    /**
     * Listing-page chrome for the shared component (title, add/import buttons,
     * status/delete reload). Index views only pass the table key.
     *
     * Set import_export_module for Import/Export. Set has_export => false when
     * the screen has Import but no Export — Customize then has no export UI.
     *
     * @return array<string, mixed>
     */
    public function listing(): array
    {
        return [];
    }

    public function roleScopeRoles(): array
    {
        return [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
        ];
    }

    /**
     * @param  array<string, mixed>  $opts
     * @return array<string, mixed>
     */
    protected function column(string $key, string $label, array $opts = []): array
    {
        return array_merge([
            'key' => $key,
            'label' => $label,
            'data' => $key,
            'name' => $key,
            'searchable' => true,
            'orderable' => true,
            'visible' => true,
            'exportable' => true,
            'html' => false,
            'permission' => null,
            'always' => false,
            'formatter' => null,
            'export_formatter' => null,
        ], $opts, ['key' => $key, 'label' => $label]);
    }

    /**
     * @param  array<string, mixed>  $opts
     * @return array<string, mixed>
     */
    protected function filter(string $key, string $type, string $label, array $opts = []): array
    {
        return array_merge([
            'key' => $key,
            'type' => $type,
            'label' => $label,
            'column' => $key,
            'permission' => null,
            'placeholder' => null,
            'options' => [],
            'apply' => null,
        ], $opts, ['key' => $key, 'type' => $type, 'label' => $label]);
    }
}
