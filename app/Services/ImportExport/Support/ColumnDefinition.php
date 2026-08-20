<?php

namespace App\Services\ImportExport\Support;

/**
 * Declares one Excel column for a module: how it maps to a model attribute,
 * how it's validated/typed, and (if relational) how it's resolved. Shared by
 * every module's Definition class - see ImportExportDefinitionContract.
 */
class ColumnDefinition
{
    /**
     * @param string $key Excel header text (exact match, case-insensitive on read)
     * @param string $attribute Model attribute name this column maps to
     * @param string $type string|integer|decimal|date|boolean|enum|relation
     * @param bool|callable $required
     * @param array $enumValues Allowed values for type=enum
     * @param RelationSpec|null $relation
     * @param array $sampleValues Example values shown in the sample file's Data sheet
     * @param string|\Closure|null $exportAccessor Dot-path or Closure(Model $model): mixed, for Export rendering
     * @param string|null $notes Extra guidance shown in the sample file's Instructions sheet
     */
    public function __construct(
        public string $key,
        public string $attribute,
        public string $type = 'string',
        public $required = false,
        public array $enumValues = [],
        public ?RelationSpec $relation = null,
        public array $sampleValues = [],
        public $exportAccessor = null,
        public ?string $notes = null,
    ) {
    }

    public function isRequired(array $rowContext = []): bool
    {
        if (is_callable($this->required)) {
            return (bool) call_user_func($this->required, $rowContext);
        }

        return (bool) $this->required;
    }
}
