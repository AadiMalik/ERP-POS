<?php

namespace App\Exports\Orders;

use App\Exports\Orders\Concerns\ComputesOrderPaymentStatus;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DueCreditSalesReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use ComputesOrderPaymentStatus;

    public function __construct(protected Collection $rows)
    {
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Order No', 'Date', 'Customer', 'Branch', 'Total Amount', 'Paid Amount', 'Due Amount', 'Payment Status'];
    }

    public function map($row): array
    {
        return [
            $row->daily_order_id,
            optional($row->order_date)->format('d-m-Y H:i'),
            $row->user->name ?? 'Walk-in',
            $row->branch->name ?? '',
            round($row->total, 2),
            round($row->paid_amount, 2),
            round($this->dueOf($row->total, $row->paid_amount), 2),
            ucwords(str_replace('_', ' ', $this->paymentStatusOf($row->total, $row->paid_amount))),
        ];
    }
}
