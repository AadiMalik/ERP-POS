<?php

namespace App\Exports\Orders;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LoyaltyReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Order No', 'Date', 'Customer', 'Order Total',
            'Points Redeemed', 'Loyalty Discount', 'Points Earned', 'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->daily_order_id,
            optional($row->order_date)->format('d-m-Y H:i'),
            $row->customer_name ?? 'Walk-in',
            round($row->total, 2),
            round($row->loyalty_points_used, 3),
            round($row->loyalty_discount_amount, 2),
            round($row->loyalty_points_earned, 3),
            ucfirst(str_replace('_', ' ', $row->status)),
        ];
    }
}
