<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ManufacturingPlanExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Plan No.', 'Plan Date', 'Business', 'Branch', 'Product', 'Planned Qty', 'Produced Qty', 'Remaining Qty', 'Progress %', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->plan_no,
            $row->plan_date,
            $row->business_name,
            $row->branch_name,
            $row->product_name,
            decimal($row->planned_quantity),
            decimal($row->produced_quantity),
            decimal($row->remaining_quantity),
            $row->progress_percentage,
            $row->status_label,
        ];
    }
}
