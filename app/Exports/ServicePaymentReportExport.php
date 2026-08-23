<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ServicePaymentReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Payment Date',
            'Type',
            'Payment No',
            'Party',
            'Reference',
            'Payment Method',
            'Account',
            'Tax Amount',
            'Discount Amount',
            'Net Amount',
            'Posted By',
        ];
    }

    public function map($row): array
    {
        return [
            localDate($row->payment_date),
            $row->payment_type,
            $row->payment_no,
            $row->party_name,
            $row->reference_no,
            ucwords(str_replace('_', ' ', $row->payment_method)),
            $row->payment_account,
            decimal($row->tax_amount),
            decimal($row->discount_amount),
            decimal($row->net_amount),
            $row->postedby,
        ];
    }
}
