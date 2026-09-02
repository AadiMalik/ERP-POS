<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FixedAssetRegisterExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Purchase Date',
            'Purchase Cost',
            'Current Book Value',
            'Accumulated Depreciation',
            'Residual Value',
            'Frequency',
            'Status',
            'Next Depreciation Date',
        ];
    }

    public function map($row): array
    {
        return [
            $row->asset_code,
            $row->name,
            $row->category,
            $row->branch,
            $row->purchase_date ? localDate($row->purchase_date) : '',
            decimal($row->purchase_cost),
            decimal($row->current_book_value),
            decimal($row->accumulated_depreciation),
            decimal($row->residual_value),
            $row->depreciation_frequency,
            $row->depreciation_status,
            $row->next_depreciation_date ? localDate($row->next_depreciation_date) : '',
        ];
    }
}
