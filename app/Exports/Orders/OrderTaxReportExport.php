<?php

namespace App\Exports\Orders;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrderTaxReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Order No', 'Date', 'Product', 'Variation', 'SKU',
            'Tax Rate (%)', 'Taxable Amount', 'Tax Amount', 'Total',
        ];
    }

    public function map($row): array
    {
        return [
            $row->daily_order_id,
            optional($row->order_date)->format('d-m-Y H:i'),
            $row->product_name,
            $row->variation_name,
            $row->sku,
            round($row->tax_rate, 2),
            round($row->taxable_amount, 2),
            round($row->tax_amount, 2),
            round($row->total, 2),
        ];
    }
}
