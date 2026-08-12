<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class IncomeReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Account Code',
            'Account Name',
            'Account Sub Type',
            'Period Debit',
            'Period Credit',
            'Net Income',
        ];
    }

    public function map($row): array
    {
        return [
            $row->account_code,
            $row->account_name,
            $row->account_subtype,
            decimal($row->period_debit),
            decimal($row->period_credit),
            decimal($row->net_amount),
        ];
    }
}
