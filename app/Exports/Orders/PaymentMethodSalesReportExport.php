<?php

namespace App\Exports\Orders;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentMethodSalesReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Payment Method', 'Orders', 'Total Amount',
        ];
    }

    public function map($row): array
    {
        return [
            $row->payment_method,
            $row->order_count,
            round($row->total_amount, 2),
        ];
    }
}
