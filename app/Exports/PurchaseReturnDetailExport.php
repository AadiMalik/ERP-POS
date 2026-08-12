<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseReturnDetailExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Return Date',
            'Return No.',
            'Return Type',
            'Source Reference',
            'Supplier',
            'Branch',
            'Warehouse',
            'Product',
            'Variation',
            'Received Qty',
            'Already Returned Qty',
            'Return Qty',
            'Unit',
            'Conversion Factor',
            'Unit Price',
            'Discount %',
            'Discount Amount',
            'Tax %',
            'Tax Amount',
            'Subtotal',
            'Total',
            'Status',
            'Posted',
            'Created By',
            'Updated By',
        ];
    }

    public function map($row): array
    {
        return [
            localDate($row->purchase_return_date),
            $row->purchase_return_no,
            $row->return_type === 'grn' ? 'GRN' : 'Direct Purchase',
            $row->source_no,
            $row->supplier_name,
            $row->branch_name,
            $row->warehouse_name,
            $row->product_name,
            $row->variation_name,
            decimal($row->received_quantity),
            decimal($row->already_returned_quantity),
            decimal($row->return_quantity),
            $row->unit_name,
            decimal($row->conversion_factor),
            decimal($row->unit_price),
            decimal($row->discount),
            decimal($row->discount_amount),
            decimal($row->tax),
            decimal($row->tax_amount),
            decimal($row->subtotal),
            decimal($row->total),
            $row->status_label,
            $row->posted,
            $row->created_by_name,
            $row->updated_by_name,
        ];
    }
}
