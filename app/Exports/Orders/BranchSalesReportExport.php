<?php

namespace App\Exports\Orders;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BranchSalesReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Branch', 'Orders', 'Qty Sold', 'Gross Sales', 'Discount', 'Tax', 'Net Sales', 'Paid', 'Due',
        ];
    }

    public function map($row): array
    {
        return [
            $row->branch,
            $row->order_count,
            round($row->total_qty, 2),
            round($row->gross, 2),
            round($row->discount, 2),
            round($row->tax, 2),
            round($row->net, 2),
            round($row->paid_amount, 2),
            round($row->due_amount, 2),
        ];
    }
}
