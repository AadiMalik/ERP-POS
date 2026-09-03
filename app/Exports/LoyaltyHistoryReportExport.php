<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LoyaltyHistoryReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Customer', 'Reference', 'Transaction Type', 'Points',
            'Monetary Value', 'Date', 'Balance After',
        ];
    }

    public function map($row): array
    {
        return [
            $row->customer_name ?? 'N/A',
            $this->referenceText($row),
            ucfirst($row->transaction_type),
            round($row->points, 3),
            $row->monetary_value !== null ? round($row->monetary_value, 2) : '',
            optional($row->date_created)->format('d-m-Y H:i'),
            round($row->available_balance_after, 3),
        ];
    }

    protected function referenceText($row): string
    {
        if ('order' === $row->reference_type && !empty($row->reference_order_no)) {
            return (string) $row->reference_order_no;
        }

        if (empty($row->reference_type)) {
            return 'N/A';
        }

        return ucfirst(str_replace('_', ' ', $row->reference_type)) . (!empty($row->reference_id) ? ' #' . $row->reference_id : '');
    }
}
