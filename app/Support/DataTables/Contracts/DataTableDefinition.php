<?php

namespace App\Support\DataTables\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Module-specific DataTable contract. A new ERP list screen only implements
 * this (query, columns, filters, permissions, formatters, listing chrome).
 * Searching, sorting, pagination, column visibility, and export live in
 * the shared engine — never duplicate them in a module service.
 */
interface DataTableDefinition
{
    public function key(): string;

    public function label(): string;

    /** Spatie permission required to open / query this table (e.g. customer.view). */
    public function permission(): string;

    /** Spatie permission required to export (e.g. customer.export). */
    public function exportPermission(): string;

    public function query(): Builder;

    /**
     * Column definitions. See AbstractDataTable::column() for the shape.
     *
     * @return array<int, array<string, mixed>>
     */
    public function columns(): array;

    /**
     * Filter definitions. See AbstractDataTable::filter() for the shape.
     *
     * @return array<int, array<string, mixed>>
     */
    public function filters(): array;

    /** Eloquent / request field used as the row identity. */
    public function rowId(): string;

    /**
     * Optional subscription module key (e.g. inventory) so DataTable endpoints
     * that sit outside a module: middleware group still honour package gating.
     */
    public function moduleKey(): ?string;

    /**
     * @return array{column: string, dir: string}
     */
    public function defaultSort(): array;

    public function defaultPageLength(): int;

    /**
     * Card title, add URL, import/export module, status/delete hooks for
     * the shared <x-erp-data-table /> component. Optional has_export => false
     * hides Export (button and Customize export ticks) for import-only lists.
     *
     * @return array<string, mixed>
     */
    public function listing(): array;

    /**
     * Roles that may pass applyRoleScope() without a 403. Empty = skip the
     * allow-list check and still apply business/branch scoping.
     *
     * @return array<int, string>
     */
    public function roleScopeRoles(): array;
}
