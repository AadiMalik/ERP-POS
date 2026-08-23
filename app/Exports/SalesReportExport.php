<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Order No',
            'Date',
            'Customer',
            'Warehouse',
            'Subtotal',
            'Discount',
            'Voucher',
            'Tax',
            'Total',
            'Paid',
        ];
    }

    public function map($row): array
    {
        return [
            $row->daily_order_id,
            optional($row->order_date)->format('d-m-Y H:i'),
            optional($row->user)->name ?? 'Walk-in',
            optional($row->warehouse)->name ?? '',
            decimal($row->subtotal),
            decimal($row->discount_amount),
            decimal($row->voucher_discount_amount),
            decimal($row->tax_amount),
            decimal($row->total),
            decimal($row->paid_amount),
        ];
    }
}
