<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Production No.', 'Plan No.', 'Business', 'Branch', 'Product', 'Warehouse', 'Batch No.', 'Mfg. Date', 'Expiry Date', 'Qty', 'Material Cost', 'Labor Cost', 'Overhead Cost', 'Other Cost', 'Total Cost', 'Unit Cost', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->production_no,
            $row->plan_no,
            $row->business_name,
            $row->branch_name,
            $row->product_name,
            $row->warehouse_name,
            $row->batch_no,
            $row->manufacturing_date,
            $row->expiry_date,
            decimal($row->quantity),
            decimal($row->material_cost),
            decimal($row->labor_cost),
            decimal($row->overhead_cost),
            decimal($row->other_cost),
            decimal($row->total_cost),
            decimal($row->unit_cost),
            $row->status_label,
        ];
    }
}
