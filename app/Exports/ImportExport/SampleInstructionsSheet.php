<?php

namespace App\Exports\ImportExport;

use App\Services\ImportExport\Contracts\ImportExportDefinitionContract;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SampleInstructionsSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(protected ImportExportDefinitionContract $def)
    {
    }

    public function title(): string
    {
        return 'Instructions & Reference';
    }

    public function headings(): array
    {
        return ['Column', 'Required?', 'Type', 'Notes'];
    }

    public function array(): array
    {
        $rows = [];
        $allColumns = $this->def->columns();

        if ($child = $this->def->childDefinition()) {
            $allColumns = array_merge($allColumns, $child->columns);
        }

        foreach ($allColumns as $col) {
            $notes = $col->notes ?? '';

            if ($col->relation) {
                $relNote = "Copy the exact {$col->relation->relatedLabelColumn} value from the {$col->relation->label()} list"
                    . ($col->relation->allowMultiple ? ' (comma-separate multiple values).' : '.');
                $notes = trim($notes . ' ' . $relNote);
            }

            $rows[] = [$col->key, $col->isRequired() ? 'Yes' : 'No', ucfirst($col->type), $notes];
        }

        $rows[] = ['', '', '', ''];
        $rows[] = ['Maximum rows per file', '', '', (string) $this->def->maxRows()];
        $rows[] = ['Date format', '', '', 'YYYY-MM-DD (e.g. 2026-08-20)'];
        $rows[] = ['How updates are matched', '', '', 'A row matching an existing record by (' . implode(', ', $this->def->uniqueKeyColumns() ?: ['-']) . ') updates that record instead of creating a duplicate.'];

        if ($this->def->groupKeyColumn()) {
            $rows[] = ['Multi-line records', '', '', 'Repeat the "' . $this->def->groupKeyColumn() . '" value on every line-item row. Header columns only need to be filled in on the first line of each group.'];
        }

        return $rows;
    }
}
