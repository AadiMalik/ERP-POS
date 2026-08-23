<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ServiceTransactionSummaryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Group',
            'Sale Amount',
            'Sale Return Amount',
            'Net Sale',
            'Purchase Amount',
            'Purchase Return Amount',
            'Net Purchase',
        ];
    }

    public function map($row): array
    {
        return [
            $row->group_label,
            decimal($row->sale_amount),
            decimal($row->sale_return_amount),
            decimal($row->net_sale_amount),
            decimal($row->purchase_amount),
            decimal($row->purchase_return_amount),
            decimal($row->net_purchase_amount),
        ];
    }
}
