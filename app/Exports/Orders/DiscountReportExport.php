<?php

namespace App\Exports\Orders;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DiscountReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Order No', 'Date', 'Customer', 'Product', 'Variation', 'SKU',
            'Discount Type', 'Discount Amount', 'Net Amount',
        ];
    }

    public function map($row): array
    {
        return [
            $row->daily_order_id,
            optional($row->order_date)->format('d-m-Y H:i'),
            $row->customer_name ?? 'Walk-in',
            $row->product_name,
            $row->variation_name,
            $row->sku,
            $row->discount_type ? ucfirst($row->discount_type) : 'N/A',
            round($row->discount_amount, 2),
            round($row->net_amount, 2),
        ];
    }
}
