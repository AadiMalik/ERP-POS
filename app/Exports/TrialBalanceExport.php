<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TrialBalanceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Account Type',
            'Opening Debit',
            'Opening Credit',
            'Period Debit',
            'Period Credit',
            'Closing Debit',
            'Closing Credit',
        ];
    }

    public function map($row): array
    {
        return [
            $row->account_code,
            $row->account_name,
            $row->account_type,
            decimal($row->opening_debit),
            decimal($row->opening_credit),
            decimal($row->period_debit),
            decimal($row->period_credit),
            decimal($row->closing_debit),
            decimal($row->closing_credit),
        ];
    }
}
