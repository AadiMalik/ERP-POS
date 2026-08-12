<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseReturnSummaryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Returns',
            'Qty',
            'Subtotal',
            'Discount',
            'Tax',
            'Total',
            'Posted',
            'Unposted',
        ];
    }

    public function map($row): array
    {
        return [
            $row->group_label,
            $row->return_count,
            decimal($row->total_qty),
            decimal($row->total_subtotal),
            decimal($row->total_discount),
            decimal($row->total_tax),
            decimal($row->total_amount),
            $row->posted_count,
            $row->unposted_count,
        ];
    }
}
