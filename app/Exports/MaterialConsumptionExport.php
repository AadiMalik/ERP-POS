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
        return [
            'Date', 'Group', 'Raw Material', 'Finished', 'Batch', 'Actual Qty', 'Expected', 'Variance',
            'Var %', 'Efficiency %', 'Unit Cost', 'Total Cost', 'Warehouse', 'Production No.', 'Plan No.',
        ];
    }

    public function map($row): array
    {
        return [
            $row->date_created ? localDate($row->date_created) : '',
            $row->group_label ?? '',
            $row->raw_material_name,
            $row->finished_product ?? '',
            $row->batch_no,
            decimal($row->base_quantity),
            $row->expected_qty !== null ? decimal($row->expected_qty) : '',
            $row->variance_qty !== null ? decimal($row->variance_qty) : '',
            $row->variance_pct !== null ? $row->variance_pct : '',
            $row->efficiency_pct !== null ? $row->efficiency_pct : '',
            decimal($row->unit_cost),
            decimal($row->total_cost),
            $row->warehouse_name,
            $row->production_no,
            $row->plan_no,
        ];
    }
}
