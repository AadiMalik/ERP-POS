<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalaryComponentReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Name', 'Code', 'Type', 'Calculation Type', 'Usage Count', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->code,
            ucfirst($row->type),
            ucfirst(str_replace('_', ' ', $row->calculation_type)),
            $row->usage_count,
            ucfirst($row->status),
        ];
    }
}
