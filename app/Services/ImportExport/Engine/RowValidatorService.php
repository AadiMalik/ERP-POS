<?php

namespace App\Services\ImportExport\Engine;

use App\Services\ImportExport\Support\ColumnDefinition;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Throwable;

/**
 * Type/format validation for one cell against its ColumnDefinition - runs
 * before relation resolution/duplicate detection so a malformed date or
 * missing required field produces a clean, specific error.
 */
class RowValidatorService
{
    public function validateCell(ColumnDefinition $col, $value, array $rowContext = []): array
    {
        $isBlank = $value === null || trim((string) $value) === '';

        if ($isBlank) {
            if ($col->type !== 'relation' && $col->isRequired($rowContext)) {
                return ['error' => ['column' => $col->key, 'value' => '', 'reason' => "\"{$col->key}\" is required."], 'value' => null];
            }

            return ['error' => null, 'value' => null];
        }

        switch ($col->type) {
            case 'integer':
                if (!is_numeric($value)) {
                    return ['error' => $this->error($col, $value, 'is not a valid whole number.'), 'value' => null];
                }

                return ['error' => null, 'value' => (int) $value];

            case 'decimal':
                if (!is_numeric($value)) {
                    return ['error' => $this->error($col, $value, 'is not a valid number.'), 'value' => null];
                }

                return ['error' => null, 'value' => (float) $value];

            case 'date':
                try {
                    $date = Carbon::parse((string) $value);
                } catch (Throwable $e) {
                    return ['error' => $this->error($col, $value, 'is not a valid date.'), 'value' => null];
                }

                return ['error' => null, 'value' => $date->format('Y-m-d')];

            case 'boolean':
                $normalized = strtolower(trim((string) $value));
                $truthy = ['1', 'true', 'yes', 'active', 'y'];
                $falsy = ['0', 'false', 'no', 'inactive', 'n'];

                if (!in_array($normalized, array_merge($truthy, $falsy), true)) {
                    return ['error' => $this->error($col, $value, 'is not a valid yes/no value.'), 'value' => null];
                }

                return ['error' => null, 'value' => in_array($normalized, $truthy, true)];

            case 'enum':
                $match = Arr::first($col->enumValues, fn ($v) => strcasecmp((string) $v, (string) $value) === 0);

                if ($match === null) {
                    return ['error' => $this->error($col, $value, 'is not one of: ' . implode(', ', $col->enumValues) . '.'), 'value' => null];
                }

                return ['error' => null, 'value' => $match];

            case 'relation':
                // Relation resolution happens separately in RelationResolverService;
                // here we only pass the trimmed raw value through.
                return ['error' => null, 'value' => trim((string) $value)];

            case 'string':
            default:
                return ['error' => null, 'value' => trim((string) $value)];
        }
    }

    protected function error(ColumnDefinition $col, $value, string $reason): array
    {
        return ['column' => $col->key, 'value' => $value, 'reason' => "\"{$value}\" " . $reason];
    }
}
