<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GeneralLedgerExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected array $result)
    {
    }

    public function collection()
    {
        return $this->result['rows'];
    }

    public function headings(): array
    {
        return [
            'Account',
            'Date',
            'Voucher Type',
            'JV Number',
            'Reference Number',
            'Narration',
            'Debit',
            'Credit',
            'Running Balance',
        ];
    }

    public function map($row): array
    {
        return [
            trim(($row->account_code ?? '') . ' ' . ($row->account_name ?? '')),
            $row->entry_date ? localDate($row->entry_date) : '',
            $row->voucher_name ?? $row->source_type,
            $row->entry_no,
            $row->reference_no,
            $row->detail_description ?: $row->entry_description,
            $row->debit > 0 ? decimal($row->debit) : '',
            $row->credit > 0 ? decimal($row->credit) : '',
            decimal($row->running_balance) . ' ' . $row->running_balance_type,
        ];
    }
}
