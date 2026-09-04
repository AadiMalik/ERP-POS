<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Generic Excel/CSV export for Inventory Reporting System master reports.
 * Pass headings + ordered property keys (or a map callable).
 */
class InventoryReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        protected Collection $rows,
        protected array $headings,
        protected array $keys
    ) {
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        $out = [];
        foreach ($this->keys as $key) {
            $value = is_array($row) ? ($row[$key] ?? '') : ($row->{$key} ?? '');
            $out[] = $value;
        }

        return $out;
    }
}
