<?php

namespace App\Exports\Orders;

use App\Exports\Orders\Concerns\ComputesOrderPaymentStatus;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrderDetailReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return [
            'Order No', 'Date/Time', 'Customer', 'Branch', 'Order Source', 'Order Status', 'Payment Status',
            'Product', 'Variation', 'SKU', 'Qty', 'Unit Price', 'Discount', 'Tax', 'Delivery Charge', 'Final Amount',
        ];
    }

    public function map($row): array
    {
        return [
            $row->daily_order_id,
            optional($row->order_date)->format('d-m-Y H:i'),
            $row->customer_name ?? 'Walk-in',
            $row->branch_name ?? '',
            $row->order_source_name ?? '',
            ucfirst(str_replace('_', ' ', $row->order_status)),
            ucwords(str_replace('_', ' ', $this->paymentStatusOf($row->order_total, $row->order_paid_amount))),
            $row->product_name ?? '',
            $row->variation_name ?? '',
            $row->sku ?? '',
            round($row->quantity, 3),
            round($row->unit_price, 2),
            round($row->discount_amount, 2),
            round($row->tax_amount, 2),
            0,
            round($row->total, 2),
        ];
    }
}
