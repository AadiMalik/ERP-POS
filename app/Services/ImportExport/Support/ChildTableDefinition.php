<?php

namespace App\Services\ImportExport\Support;

/**
 * Declares the child/detail table for a master/child module (e.g.
 * TransferNoteDetail under TransferNote). Child rows are grouped under a
 * parent by the parent Definition's groupKeyColumn().
 */
class ChildTableDefinition
{
    /**
     * @param array $columns ColumnDefinition[] for the child row
     */
    public function __construct(
        public string $modelClass,
        public string $primaryKey,
        public string $foreignKeyAttribute,
        public array $columns,
        public int $minChildren = 1,
        public bool $deleteExistingOnUpdate = true,
    ) {
    }
}
