<?php

namespace App\Exports\ImportExport;

use App\Services\ImportExport\Contracts\ImportExportDefinitionContract;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SampleDataSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(protected ImportExportDefinitionContract $def)
    {
    }

    public function title(): string
    {
        return 'Data';
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

    public function array(): array
    {
        $columns = $this->def->columns();
        $child = $this->def->childDefinition();

        if (!$child) {
            $rows = [];
            for ($i = 0; $i < 2; $i++) {
                $rows[] = array_map(fn ($c) => $c->sampleValues[$i] ?? ($c->sampleValues[0] ?? ''), $columns);
            }

            return $rows;
        }

        // Master/child: two example groups. The second demonstrates the
        // repeated-group-key convention (2 line items, parent columns blank
        // on the second line).
        $childCols = $child->columns;
        $rows = [];

        $rows[] = array_merge(
            array_map(fn ($c) => $c->sampleValues[0] ?? '', $columns),
            array_map(fn ($c) => $c->sampleValues[0] ?? '', $childCols)
        );

        $secondGroupParentRow = array_map(fn ($c) => $c->sampleValues[1] ?? ($c->sampleValues[0] ?? ''), $columns);

        $rows[] = array_merge(
            $secondGroupParentRow,
            array_map(fn ($c) => $c->sampleValues[1] ?? ($c->sampleValues[0] ?? ''), $childCols)
        );

        // Second line of the second group: only the group-key column repeats
        // (as instructed); every other parent column is left blank.
        $groupKeyColumn = $this->def->groupKeyColumn();
        $repeatedGroupKeyRow = array_map(
            fn ($c) => $c->key === $groupKeyColumn ? $secondGroupParentRow[array_search($c, $columns, true)] : '',
            $columns
        );

        $rows[] = array_merge(
            $repeatedGroupKeyRow,
            array_map(fn ($c) => $c->sampleValues[2] ?? ($c->sampleValues[0] ?? ''), $childCols)
        );

        return $rows;
    }
}
