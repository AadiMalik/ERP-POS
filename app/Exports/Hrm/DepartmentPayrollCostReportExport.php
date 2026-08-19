<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DepartmentPayrollCostReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Department', 'Employees', 'Total Earnings', 'Total Advances', 'Total Cost'];
    }

    public function map($row): array
    {
        return [
            $row->department,
            $row->employee_count,
            decimal($row->total_earnings),
            decimal($row->total_advances),
            decimal($row->total_cost),
        ];
    }
}
