<?php

namespace App\Services\ImportExport\Support;

use App\Services\ImportExport\Contracts\ImportExportDefinitionContract;
use App\Services\ImportExport\Engine\DuplicateDetectorService;
use App\Services\ImportExport\Engine\RelationResolverService;
use App\Services\ImportExport\Engine\RowValidatorService;

/**
 * Generic resolveRow()/save() implementation shared by every module's
 * Definition class. A concrete Definition only needs to declare its
 * columns/keys/relations/scoping and override the small hook methods below
 * when it needs domain-specific behavior (extra validation, computed
 * totals, non-standard save logic).
 */
abstract class AbstractImportExportDefinition implements ImportExportDefinitionContract
{
    public function isBusinessScoped(): bool
    {
        return true;
    }

    public function isBranchScoped(): bool
    {
        return false;
    }

    public function uniqueKeyIsGlobal(): bool
    {
        return false;
    }

    public function childDefinition(): ?ChildTableDefinition
    {
        return null;
    }

    public function groupKeyColumn(): ?string
    {
        return null;
    }

    public function childRelationName(): ?string
    {
        return null;
    }

    public function maxRows(): int
    {
        return 2000;
    }

    public function canImport(): bool
    {
        return true;
    }

    public function canExport(): bool
    {
        return true;
    }

    public function exportEagerLoads(): array
    {
        return [];
    }

    // ------------------------------------------------------------------
    // Preview / resolution
    // ------------------------------------------------------------------

    public function resolveRow(array $group, ImportContext $ctx): ResolvedRow
    {
        $rows = $group['rows'];
        $firstRow = $rows[0];
        $columns = $this->columns();

        [$attributes, $errors, $warnings] = $this->resolveColumns($columns, $firstRow, $ctx, $rows);

        $action = 'invalid';
        $matchedId = null;

        if (empty($errors)) {
            $duplicate = app(DuplicateDetectorService::class)->detect($attributes, $this, $ctx);
            $action = $duplicate['action'];
            $matchedId = $duplicate['matchedId'];
        }

        $parent = new ResolvedRow(
            rowNumber: $firstRow['row_number'],
            groupKey: $group['group_key'],
            action: empty($errors) ? $action : 'invalid',
            matchedId: $matchedId,
            attributes: $attributes,
            raw: $firstRow['raw'],
            errors: $errors,
            warnings: $warnings,
        );

        if ($childDef = $this->childDefinition()) {
            $children = [];
            foreach ($rows as $row) {
                $children[] = $this->resolveChildRow($childDef, $row, $ctx);
            }
            $parent->children = $children;

            $validChildren = array_filter($children, fn (ResolvedRow $c) => $c->action !== 'invalid');
            if (count($validChildren) < $childDef->minChildren) {
                $parent->action = 'invalid';
                $parent->errors[] = [
                    'column' => $this->groupKeyColumn(),
                    'value' => $group['group_key'],
                    'reason' => "This record has fewer than {$childDef->minChildren} valid line item(s), so it cannot be imported.",
                ];
            }
        }

        $this->applyDomainValidation($parent, $ctx);

        return $parent;
    }

    protected function resolveColumns(array $columns, array $row, ImportContext $ctx, array $groupRows = []): array
    {
        $attributes = [];
        $errors = [];
        $warnings = [];

        foreach ($columns as $col) {
            $cellValue = $row['raw'][$col->key] ?? null;

            // Later rows in a group may repeat the group key with a
            // different, non-blank parent-column value - warn, don't error.
            if (count($groupRows) > 1 && $row === $groupRows[0]) {
                foreach (array_slice($groupRows, 1) as $laterRow) {
                    $laterValue = $laterRow['raw'][$col->key] ?? null;
                    if ($laterValue !== null && trim((string) $laterValue) !== '' && (string) $laterValue !== (string) $cellValue) {
                        $warnings[] = [
                            'column' => $col->key,
                            'value' => $laterValue,
                            'reason' => "Row {$laterRow['row_number']} repeats \"{$col->key}\" with a different value; the first row's value was used.",
                        ];
                    }
                }
            }

            $validated = app(RowValidatorService::class)->validateCell($col, $cellValue, $row['raw']);
            if ($validated['error']) {
                $errors[] = $validated['error'];
                continue;
            }

            if ($col->type === 'relation' && $col->relation) {
                $resolved = app(RelationResolverService::class)->resolve($col->relation, $validated['value'], $ctx);

                if ($resolved['error']) {
                    if ($cellValue !== null && trim((string) $cellValue) !== '') {
                        $errors[] = ['column' => $col->key, 'value' => $cellValue, 'reason' => $resolved['error']];
                    } elseif ($col->isRequired($row['raw'])) {
                        $errors[] = ['column' => $col->key, 'value' => '', 'reason' => "\"{$col->key}\" is required."];
                    }
                    continue;
                }

                $attributes[$col->attribute] = $col->relation->allowMultiple ? $resolved['ids'] : $resolved['id'];
            } else {
                $attributes[$col->attribute] = $validated['value'];
            }
        }

        return [$attributes, $errors, $warnings];
    }

    protected function resolveChildRow(ChildTableDefinition $childDef, array $row, ImportContext $ctx): ResolvedRow
    {
        [$attributes, $errors] = $this->resolveColumns($childDef->columns, $row, $ctx);

        return new ResolvedRow(
            rowNumber: $row['row_number'],
            groupKey: null,
            action: empty($errors) ? 'create' : 'invalid',
            attributes: $attributes,
            raw: $row['raw'],
            errors: $errors,
        );
    }

    /**
     * Override for cross-field domain rules (e.g. debit must equal credit
     * across a Journal Entry's lines, stock availability checks) that can't
     * be expressed as a single column's validation. Push failures into
     * $row->errors (and set $row->action = 'invalid') rather than throwing.
     */
    protected function applyDomainValidation(ResolvedRow $row, ImportContext $ctx): void
    {
    }

    // ------------------------------------------------------------------
    // Save
    // ------------------------------------------------------------------

    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        $modelClass = $this->modelClass();
        $pk = $this->primaryKey();
        // Blank/omitted optional cells resolve to null; strip them so they
        // never overwrite an existing value on update or a default on create.
        $providedAttributes = array_filter($row->attributes, fn ($v) => $v !== null);
        $attributes = array_merge($providedAttributes, $this->contextAttributes($ctx));

        if ($row->action === 'update') {
            $model = $modelClass::findOrFail($row->matchedId);
            $model->update(array_merge($attributes, [
                'updatedby_id' => $ctx->userId,
                'date_updated' => now(),
            ]));
            $created = false;
        } else {
            $createAttributes = array_merge(
                $this->additionalCreateAttributes($ctx),
                $attributes,
                [
                    $pk => generateUuid(),
                    'createdby_id' => $ctx->userId,
                    'date_created' => now(),
                ]
            );
            $model = $modelClass::create($createAttributes);
            $created = true;
        }

        if ($childDef = $this->childDefinition()) {
            if (!$created && $childDef->deleteExistingOnUpdate) {
                $childModelClass = $childDef->modelClass;
                $childModelClass::where($childDef->foreignKeyAttribute, $model->{$pk})->delete();
            }

            foreach ($row->children as $child) {
                if ($child->action === 'invalid') {
                    continue;
                }

                $childModelClass = $childDef->modelClass;
                $childAttributes = array_merge($this->mapChildAttributes($child->attributes), [
                    $childDef->primaryKey => generateUuid(),
                    $childDef->foreignKeyAttribute => $model->{$pk},
                    'createdby_id' => $ctx->userId,
                    'date_created' => now(),
                ]);
                $childModelClass::create($childAttributes);
            }
        }

        $this->afterSave($model, $row, $created, $ctx);

        return ['model' => $model, 'created' => $created];
    }

    /**
     * business_id/branch_id (and any other context-derived attributes)
     * merged into every create/update.
     */
    protected function contextAttributes(ImportContext $ctx): array
    {
        $attrs = [];
        if ($this->isBusinessScoped() && $ctx->businessId) {
            $attrs['business_id'] = $ctx->businessId;
        }
        if ($this->isBranchScoped() && $ctx->branchId) {
            $attrs['branch_id'] = $ctx->branchId;
        }

        return $attrs;
    }

    /** Extra attributes only applied on create (e.g. a default status). */
    protected function additionalCreateAttributes(ImportContext $ctx): array
    {
        return [];
    }

    /** Override to remap/derive child attributes before insert. */
    protected function mapChildAttributes(array $attributes): array
    {
        return $attributes;
    }

    /** Override to recompute parent totals from saved children, etc. */
    protected function afterSave($model, ResolvedRow $row, bool $created, ImportContext $ctx): void
    {
    }
}
