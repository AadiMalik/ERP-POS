<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TaxReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Opening Balance',
            'Period Debit',
            'Period Credit',
            'Closing Balance',
        ];
    }

    public function map($row): array
    {
        return [
            $row->account_code,
            $row->account_name,
            decimal($row->opening_balance) . ' ' . $row->opening_balance_type,
            decimal($row->period_debit),
            decimal($row->period_credit),
            decimal($row->closing_balance) . ' ' . $row->closing_balance_type,
        ];
    }
}
