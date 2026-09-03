<?php

namespace App\Exports\Orders;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrderSourceSalesReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Order Source', 'Orders', 'Qty', 'Gross Sales', 'Net Sales',
        ];
    }

    public function map($row): array
    {
        return [
            $row->order_source,
            $row->order_count,
            round($row->total_qty, 2),
            round($row->gross, 2),
            round($row->net, 2),
        ];
    }
}
