<?php

namespace App\Exports;

use App\Services\ImportExport\Contracts\ImportExportDefinitionContract;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * The one Export class shared by every CRUD module - renders relation
 * columns as names (via each ColumnDefinition::exportAccessor), not raw IDs.
 * For master/child modules, one Excel row is produced per child line with
 * parent columns repeated, mirroring the import convention exactly so an
 * exported file is directly re-importable.
 */
class GenericImportExportModuleExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Builder $query, protected ImportExportDefinitionContract $def)
    {
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        $headings = array_map(fn ($c) => $c->key, $this->def->columns());

        if ($child = $this->def->childDefinition()) {
            foreach ($child->columns as $c) {
                $headings[] = $c->key;
            }
        }

        return $headings;
    }

    public function map($model): array
    {
        $child = $this->def->childDefinition();

        if (!$child) {
            return [$this->mapColumns($this->def->columns(), $model)];
        }

        $relation = $this->def->childRelationName();
        $children = $relation ? $model->{$relation} : collect();

        if (blank($children) || $children->isEmpty()) {
            return [array_merge($this->mapColumns($this->def->columns(), $model), array_fill(0, count($child->columns), ''))];
        }

        return $children->map(function ($childModel) use ($model, $child) {
            return array_merge($this->mapColumns($this->def->columns(), $model), $this->mapColumns($child->columns, $childModel));
        })->all();
    }

    protected function mapColumns(array $columns, $model): array
    {
        return array_map(function ($col) use ($model) {
            if ($col->exportAccessor instanceof \Closure) {
                return ($col->exportAccessor)($model);
            }

            $path = $col->exportAccessor ?? $col->attribute;

            return data_get($model, $path) ?? '';
        }, $columns);
    }
}
