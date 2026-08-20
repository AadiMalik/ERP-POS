<?php

namespace App\Services\ImportExport\Contracts;

use App\Services\ImportExport\Support\ChildTableDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;

/**
 * Everything the generic Import/Export engine needs to know about one
 * module. Implemented once per module (usually via AbstractImportExportDefinition)
 * and registered in ImportExportModuleRegistry.
 */
interface ImportExportDefinitionContract
{
    public function moduleKey(): string;

    public function label(): string;

    public function modelClass(): string;

    public function primaryKey(): string;

    public function isBusinessScoped(): bool;

    public function isBranchScoped(): bool;

    /** @return \App\Services\ImportExport\Support\ColumnDefinition[] */
    public function columns(): array;

    /** @return string[] */
    public function uniqueKeyColumns(): array;

    public function uniqueKeyIsGlobal(): bool;

    public function childDefinition(): ?ChildTableDefinition;

    /** Excel header used to group child rows under one parent; null for simple modules */
    public function groupKeyColumn(): ?string;

    /** Eloquent relation name on the parent model for its children, used by export; null for simple modules */
    public function childRelationName(): ?string;

    public function maxRows(): int;

    public function canImport(): bool;

    public function canExport(): bool;

    /**
     * Resolve one parent group (a simple module's group always has exactly
     * one row) into a ResolvedRow, including child rows if applicable.
     *
     * @param array $group ['group_key' => ?string, 'rows' => [['row_number' => int, 'raw' => array], ...]]
     */
    public function resolveRow(array $group, ImportContext $ctx): ResolvedRow;

    /**
     * Persist one resolved, valid row (create or update), including its
     * children if any.
     *
     * @return array{model: \Illuminate\Database\Eloquent\Model, created: bool}
     */
    public function save(ResolvedRow $row, ImportContext $ctx): array;

    public function exportQuery(array $filters, ImportContext $ctx): Builder;

    public function exportEagerLoads(): array;
}
