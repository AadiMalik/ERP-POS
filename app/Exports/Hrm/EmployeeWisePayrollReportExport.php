<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeWisePayrollReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Employee Code', 'Name', 'Department', 'Period', 'Basic Salary', 'Total Earnings',
            'Total Deductions', 'Overtime', 'Advance Deduction', 'Net Salary', 'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee?->employee_code,
            $row->employee?->user?->name,
            $row->employee?->department?->name,
            $row->payrollRun ? (date('F', mktime(0, 0, 0, $row->payrollRun->month, 1)) . ' ' . $row->payrollRun->year) : '-',
            decimal($row->basic_salary),
            decimal($row->total_earnings),
            decimal($row->total_deductions),
            decimal($row->overtime_amount),
            decimal($row->advance_deduction),
            decimal($row->net_salary),
            ucfirst($row->status),
        ];
    }
}
