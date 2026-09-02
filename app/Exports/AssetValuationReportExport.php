<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetValuationReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Collection $rows)
    {
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Category',
            'Branch',
            'Purchase Cost',
            'Accumulated Depreciation',
            'Current Book Value',
            'Previous Book Value',
            'Residual Value',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->asset_code,
            $row->name,
            $row->category,
            $row->branch,
            decimal($row->purchase_cost),
            decimal($row->accumulated_depreciation),
            decimal($row->current_book_value),
            decimal($row->previous_book_value),
            decimal($row->residual_value),
            $row->depreciation_status,
        ];
    }
}
