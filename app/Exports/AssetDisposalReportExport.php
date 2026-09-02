<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetDisposalReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Disposal Date',
            'Disposal Type',
            'Sale Price',
            'Book Value',
            'Purchase Cost',
            'Accumulated Depreciation',
            'Reason',
            'Status',
            'Journal Entry',
        ];
    }

    public function map($row): array
    {
        return [
            $row->asset_code,
            $row->name,
            $row->category,
            $row->branch,
            $row->disposal_date ? localDate($row->disposal_date) : '',
            $row->disposal_type,
            decimal($row->sale_price),
            decimal($row->current_book_value),
            decimal($row->purchase_cost),
            decimal($row->accumulated_depreciation),
            $row->disposal_reason,
            $row->depreciation_status,
            $row->journal_entry,
        ];
    }
}
