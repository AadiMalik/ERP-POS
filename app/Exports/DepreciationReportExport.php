<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DepreciationReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Date',
            'Period',
            'Asset Code',
            'Asset Name',
            'Branch',
            'Previous Value',
            'Depreciation Amount',
            'New Value',
            'Accumulated Depreciation',
            'Journal Entry',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->depreciation_date ? localDate($row->depreciation_date) : '',
            $row->period_key,
            $row->asset_code,
            $row->asset_name,
            $row->branch,
            decimal($row->previous_value),
            decimal($row->depreciation_amount),
            decimal($row->new_value),
            decimal($row->accumulated_depreciation),
            $row->journal_entry,
            $row->status,
        ];
    }
}
