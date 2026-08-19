<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeLifecycleReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Employee Code', 'Name', 'Department', 'Designation', 'Joining Date', 'Status',
            'Present Days', 'Absent Days', 'Late Days', 'Leave Days',
            'Total Leave Requests', 'Total Advances', 'Exit Type', 'Exit Status',
        ];
    }

    public function map($row): array
    {
        $employee = $row->employee;

        return [
            $employee->employee_code,
            $employee->user?->name,
            $employee->department?->name,
            $employee->designation?->name,
            localDate($employee->joining_date),
            ucfirst(str_replace('_', ' ', $employee->status)),
            $row->attendance_present,
            $row->attendance_absent,
            $row->attendance_late,
            $row->attendance_leave,
            $row->leave_requests->count(),
            $row->advances->count(),
            $row->exit?->type ? ucfirst($row->exit->type) : '-',
            $row->exit?->status ? ucfirst($row->exit->status) : '-',
        ];
    }
}
