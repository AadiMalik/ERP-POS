<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayrollCostReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Period', 'Employees', 'Total Earnings', 'Total Deductions', 'Total Cost', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->period,
            $row->employee_count,
            decimal($row->total_earnings),
            decimal($row->total_deductions),
            decimal($row->total_cost),
            ucfirst($row->status),
        ];
    }
}
