<?php

namespace App\Exports\Orders;

use App\Enums\ComplimentaryStatus;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ComplimentaryReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Collection $rows, protected bool $canViewCost = false)
    {
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        $headings = [
            'Order No', 'Date', 'Customer', 'Status', 'Product', 'Variation',
            'Qty', 'Original Selling Price', 'Complimentary Retail Value',
            'Reason', 'Warehouse', 'User',
        ];

        if ($this->canViewCost) {
            $headings[] = 'Actual Cost';
        }

        return $headings;
    }

    public function map($row): array
    {
        $mapped = [
            $row->daily_order_id,
            localDateTime($row->order_date),
            $row->customer_name ?? 'Walk-in',
            ComplimentaryStatus::getOptions()[$row->complimentary_status] ?? $row->complimentary_status,
            $row->product_name,
            $row->variation_name,
            round((float) $row->quantity, 3),
            round((float) $row->unit_price, 2),
            round((float) $row->complimentary_value, 2),
            $row->line_reason_name ?: ($row->order_reason_name ?: '-'),
            $row->warehouse_name,
            $row->issued_by_name,
        ];

        if ($this->canViewCost) {
            $mapped[] = round((float) $row->cost_price * (float) $row->base_quantity, 2);
        }

        return $mapped;
    }
}
