<?php

namespace App\Exports\DataTable;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Generic Excel/CSV export for the centralized DataTable engine.
 * Rows are already filtered, authorized, and capped by DataTableExportService
 * so this class only maps the chosen columns.
 */
class GenericDataTableExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @param  array<int, array{key: string, label: string, export_value: callable}>  $columns
     */
    public function __construct(protected Collection $rows, protected array $columns)
    {
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return array_map(fn ($column) => $column['label'], $this->columns);
    }

    public function map($row): array
    {
        $out = [];
        foreach ($this->columns as $column) {
            $out[] = ($column['export_value'])($row);
        }

        return $out;
    }
}
