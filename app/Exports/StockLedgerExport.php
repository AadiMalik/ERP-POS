<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockLedgerExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Date',
            'Source Module',
            'Reference No.',
            'Warehouse',
            'Product',
            'Variation',
            'Movement Type',
            'Qty In',
            'Qty Out',
            'Unit Cost',
            'Value',
            'Balance',
        ];
    }

    public function map($row): array
    {
        return [
            localDate($row->transaction_date),
            $row->source_module,
            $row->reference_no,
            $row->warehouse_name,
            $row->product_name,
            $row->variation_name,
            $row->transaction_type_label,
            $row->quantity_in > 0 ? decimal($row->quantity_in) : '',
            $row->quantity_out > 0 ? decimal($row->quantity_out) : '',
            decimal($row->unit_price),
            decimal($row->value),
            decimal($row->quantity_after),
        ];
    }
}
