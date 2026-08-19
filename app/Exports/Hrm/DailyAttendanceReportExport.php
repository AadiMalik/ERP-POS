<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DailyAttendanceReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Employee Code', 'Name', 'Department', 'Date', 'Check In', 'Check Out',
            'Working Hours', 'Late Minutes', 'Early Checkout Minutes', 'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee?->employee_code,
            $row->employee?->user?->name,
            $row->employee?->department?->name,
            localDate($row->date),
            $row->check_in_time,
            $row->check_out_time,
            $row->working_hours,
            $row->late_minutes,
            $row->early_leave_minutes,
            ucfirst(str_replace('_', ' ', $row->status)),
        ];
    }
}
