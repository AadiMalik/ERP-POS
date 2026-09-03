<?php

namespace App\Exports\Orders;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerSalesReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Customer', 'Total Orders', 'Total Qty', 'Gross Sales', 'Discount', 'Net Sales', 'Paid Amount', 'Due Amount',
        ];
    }

    public function map($row): array
    {
        return [
            $row->customer,
            $row->order_count,
            round($row->total_qty, 2),
            round($row->gross, 2),
            round($row->discount, 2),
            round($row->net, 2),
            round($row->paid_amount, 2),
            round($row->due_amount, 2),
        ];
    }
}
