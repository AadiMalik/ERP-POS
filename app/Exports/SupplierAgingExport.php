<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SupplierAgingExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Supplier Name',
            'Current',
            '1-30 Days',
            '31-60 Days',
            '61-90 Days',
            '91-120 Days',
            '120+ Days',
            'Total Outstanding',
            'Last Payment Date',
            'Total Balance (Ledger)',
        ];
    }

    public function map($row): array
    {
        return [
            $row->supplier_name,
            decimal($row->bucket_current),
            decimal($row->bucket_1_30),
            decimal($row->bucket_31_60),
            decimal($row->bucket_61_90),
            decimal($row->bucket_91_120),
            decimal($row->bucket_120_plus),
            decimal($row->total_outstanding),
            $row->last_payment_date ? localDate($row->last_payment_date) : 'N/A',
            decimal($row->total_balance) . ' ' . $row->total_balance_type,
        ];
    }
}
