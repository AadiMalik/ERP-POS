<?php

namespace App\Services\ImportExport\Support;

use Illuminate\Support\Str;

/**
 * Declares how a relational Excel column resolves to a related model's ID
 * (or, when $allowMultiple is true, a list of IDs for a pivot/many-to-many
 * column such as Voucher's Products/Categories/Users).
 */
class RelationSpec
{
    public function __construct(
        public string $relatedModel,
        public string $relatedModuleKey,
        public string $relatedLabelColumn = 'name',
        public bool $scopeToBusiness = true,
        public bool $scopeToBranch = false,
        public ?string $foreignKeyAttribute = null,
        public bool $allowMultiple = false,
        public ?string $pivotTable = null,
        public ?string $pivotForeignKey = null,
        public ?string $pivotRelatedKey = null,
        public ?string $relatedLabel = null,
    ) {
    }

    public function label(): string
    {
        return $this->relatedLabel ?? (string) Str::of($this->relatedModuleKey)->replace('-', ' ')->title();
    }

    public function notFoundMessage($value): string
    {
        $label = $this->label();

        return "No {$label} named \"{$value}\" found for this business. Copy the exact {$this->relatedLabelColumn} value from the {$label} list.";
    }
}
