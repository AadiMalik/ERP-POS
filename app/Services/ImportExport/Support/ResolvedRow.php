<?php

namespace App\Services\ImportExport\Support;

/**
 * The outcome of resolving one Excel row (or, for master/child modules, one
 * parent group) during preview/confirm: whether it will be created, updated,
 * or is invalid, its resolved model-ready attributes, and any errors.
 */
class ResolvedRow
{
    /**
     * @param array $errors [{column, value, reason}]
     * @param array $warnings [{column, value, reason}]
     * @param ResolvedRow[] $children
     */
    public function __construct(
        public int $rowNumber,
        public ?string $groupKey,
        public string $action,
        public $matchedId = null,
        public array $attributes = [],
        public array $raw = [],
        public array $errors = [],
        public array $warnings = [],
        public array $children = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'row_number' => $this->rowNumber,
            'group_key' => $this->groupKey,
            'action' => $this->action,
            'matched_id' => $this->matchedId,
            'data' => $this->attributes,
            'raw' => $this->raw,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'children' => array_map(fn (self $c) => $c->toArray(), $this->children),
        ];
    }
}
