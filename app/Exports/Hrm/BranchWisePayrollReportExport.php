<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BranchWisePayrollReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Branch', 'Employees', 'Gross Salary', 'Deductions', 'Net Salary'];
    }

    public function map($row): array
    {
        return [
            $row->branch,
            $row->employee_count,
            decimal($row->total_gross),
            decimal($row->total_deductions),
            decimal($row->total_net_salary),
        ];
    }
}
