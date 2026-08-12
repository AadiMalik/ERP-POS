<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DayBookExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Date',
            'Voucher Type',
            'JV Number',
            'Account',
            'Reference Number',
            'Narration',
            'Debit',
            'Credit',
        ];
    }

    public function map($row): array
    {
        return [
            localDate($row->entry_date),
            $row->voucher_name ?? $row->source_type,
            $row->entry_no,
            trim(($row->account_code ?? '') . ' ' . ($row->account_name ?? '')),
            $row->reference_no,
            $row->detail_description ?: $row->entry_description,
            $row->debit > 0 ? decimal($row->debit) : '',
            $row->credit > 0 ? decimal($row->credit) : '',
        ];
    }
}
