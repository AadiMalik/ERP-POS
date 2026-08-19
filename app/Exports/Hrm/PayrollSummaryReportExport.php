<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayrollSummaryReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Period', 'Employees', 'Gross Salary', 'Deductions', 'Advance Deduction',
            'Overtime', 'Net Salary', 'Status',
        ];
    }

    public function map($row): array
    {
        return [
            date('F', mktime(0, 0, 0, $row->month, 1)) . ' ' . $row->year,
            $row->employee_count,
            decimal($row->total_gross),
            decimal($row->total_deductions),
            decimal($row->total_advance_deduction),
            decimal($row->total_overtime),
            decimal($row->total_net_salary),
            ucfirst($row->status),
        ];
    }
}
