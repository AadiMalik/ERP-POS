<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendancePayrollComparisonReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Employee Code', 'Name', 'Department', 'Period',
            'Payslip Present', 'Actual Present', 'Payslip Absent', 'Actual Absent',
            'Payslip Leave', 'Actual Leave', 'Match Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee?->employee_code,
            $row->employee?->user?->name,
            $row->employee?->department?->name,
            $row->payrollRun ? (date('F', mktime(0, 0, 0, $row->payrollRun->month, 1)) . ' ' . $row->payrollRun->year) : '-',
            $row->present_days,
            $row->actual_present,
            $row->absent_days,
            $row->actual_absent,
            $row->leave_days,
            $row->actual_leave,
            $row->is_mismatched ? 'Mismatch' : 'Matched',
        ];
    }
}
