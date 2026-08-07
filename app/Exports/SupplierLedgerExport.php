<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SupplierLedgerExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected array $result)
    {
    }

    public function collection()
    {
        $rows = collect();

        $rows->push((object) [
            'entry_date'           => null,
            'voucher_name'         => 'Opening Balance',
            'source_type'          => null,
            'entry_no'             => '',
            'reference_no'         => '',
            'detail_description'   => 'Opening Balance',
            'entry_description'    => '',
            'debit'                => 0,
            'credit'               => 0,
            'running_balance'      => $this->result['opening_balance'],
            'running_balance_type' => $this->result['opening_balance_type'],
        ]);

        $rows = $rows->merge($this->result['rows']);

        $rows->push((object) [
            'entry_date'           => null,
            'voucher_name'         => 'Closing Balance',
            'source_type'          => null,
            'entry_no'             => '',
            'reference_no'         => '',
            'detail_description'   => 'Closing Balance',
            'entry_description'    => '',
            'debit'                => $this->result['total_debit'],
            'credit'               => $this->result['total_credit'],
            'running_balance'      => $this->result['closing_balance'],
            'running_balance_type' => $this->result['closing_balance_type'],
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Document Date',
            'Voucher Date',
            'Voucher Type',
            'Voucher Number',
            'Reference Number',
            'Description',
            'Debit',
            'Credit',
            'Running Balance',
            'Balance Type',
        ];
    }

    public function map($row): array
    {
        return [
            $row->entry_date ? localDate($row->entry_date) : '',
            $row->entry_date ? localDate($row->entry_date) : '',
            $row->voucher_name ?? $row->source_type ?? '',
            $row->entry_no,
            $row->reference_no,
            $row->detail_description ?: $row->entry_description,
            $row->debit > 0 ? decimal($row->debit) : '',
            $row->credit > 0 ? decimal($row->credit) : '',
            decimal($row->running_balance),
            $row->running_balance_type,
        ];
    }
}
