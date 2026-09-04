<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaterialConsumptionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Date', 'Raw Material', 'Batch Consumed', 'Qty', 'Unit Cost', 'Total Cost', 'Warehouse', 'Production No.', 'Plan No.'];
    }

    public function map($row): array
    {
        return [
            localDate($row->date_created),
            $row->raw_material_name,
            $row->batch_no,
            decimal($row->base_quantity),
            decimal($row->unit_cost),
            decimal($row->total_cost),
            $row->warehouse_name,
            $row->production_no,
            $row->plan_no,
        ];
    }
}
