<?php

namespace App\Exports\Orders;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductSalesReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Product', 'Total Qty Sold', 'Gross Sales', 'Discount', 'Tax', 'Net Sales'];
    }

    public function map($row): array
    {
        return [
            $row->product_name ?? '',
            round($row->total_qty, 3),
            round($row->gross, 2),
            round($row->discount, 2),
            round($row->tax, 2),
            round($row->net, 2),
        ];
    }
}
