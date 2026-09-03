<?php

namespace App\Exports\Orders;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TopSellingReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Rank', 'Product', 'Variation', 'SKU', 'Qty Sold', 'Gross', 'Discount', 'Tax', 'Net Sales',
        ];
    }

    public function map($row): array
    {
        return [
            $row->rank,
            $row->product_name ?? '',
            $row->variation_name ?? '',
            $row->sku ?? '',
            round((float) $row->total_qty, 3),
            round((float) $row->gross, 2),
            round((float) $row->discount, 2),
            round((float) $row->tax, 2),
            round((float) $row->net, 2),
        ];
    }
}
