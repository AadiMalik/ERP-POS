<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerPaymentHistoryExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Builder $query)
    {
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Payment Date',
            'Payment Number',
            'Customer',
            'Payment Method',
            'Reference Order',
            'Payment Reference Number',
            'Bank/Cash Account',
            'Tax Amount',
            'Discount Amount',
            'Net Payment',
            'Posted By',
            'Posting Status',
            'Remarks',
        ];
    }

    public function map($row): array
    {
        return [
            localDate($row->payment_date),
            $row->payment_no,
            $row->user->name ?? '',
            ucwords(str_replace('_', ' ', $row->payment_method)),
            $row->order_id ? ($row->order->daily_order_id ?? $row->order_id) : 'On Account',
            $row->reference_no,
            $row->paymentAccount->name ?? '',
            decimal($row->tax_amount),
            decimal($row->discount_amount),
            decimal($row->net_amount),
            $row->postedby->name ?? '',
            ucfirst($row->status),
            $row->remarks,
        ];
    }
}
