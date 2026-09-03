<?php

namespace App\Exports\Orders;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrderStatusReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Status', 'Orders', 'Gross Sales', 'Discount', 'Tax', 'Net Sales', 'Paid', 'Due'];
    }

    public function map($row): array
    {
        return [
            ucfirst(str_replace('_', ' ', $row->status)),
            $row->order_count,
            round($row->gross, 2),
            round($row->discount, 2),
            round($row->tax, 2),
            round($row->net, 2),
            round($row->paid, 2),
            round(max(($row->net ?? 0) - ($row->paid ?? 0), 0), 2),
        ];
    }
}
